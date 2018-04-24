<?php

namespace app\common\base;

use app\modules\message\models\MessageCenter;
use app\modules\patient\models\Patient;
use app\modules\spot_set\models\Room;
use app\modules\user\models\User;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\modules\module\models\Title;
use app\modules\module\models\Menu;
use yii\helpers\Url;
use app\modules\behavior\models\BehaviorRecord;
use yii\helpers\Json;
use app\common\Common;
use app\modules\rbac\models\AssignmentForm;
use app\modules\rbac\models\ItemChildForm;
use app\modules\spot\models\Spot;
use yii\db\Query;
use app\modules\user\models\UserSpot;
use app\modules\spot_set\models\PaymentConfig;
use yii\caching\DbDependency;
use app\modules\spot_set\models\MedicalTips;

class BaseController extends Controller
{

    public $layoutData; //用户菜单列表
    public $pageSize = 20; //分页大小
    public $userInfo; //用户的user信息;
    public $isSuperSystem = false;
    public $manager;
    public $result; //json返回容器
    private $readApi = array('index', 'view', 'list', 'get'); // 不做记录的动作

    public function init() {
        parent::init();
        $this->userInfo = Yii::$app->user->identity;
        $this->manager = Yii::$app->authManager;
        $this->result['success'] = true;
        $this->result['errorCode'] = 0; //默认为0，则没有错误
        $this->result['msg'] = '';

    }

    public function behaviors() {

        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    // 允许认证用户
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                // 默认禁止其他用户
                ],
            ],
        ];
    }

    public function beforeAction($action) {
        parent::beforeAction($action);
        $this->layout = false;
        $view = Yii::$app->view;
        $moduleId = \Yii::$app->controller->module->id;
        $controllerId = Yii::$app->controller->id;
        $requestUrl = '/' . $moduleId . '/' . $controllerId . '/' . $action->id; // 用户访问的路径
        $view->params['requestModuleController'] = '/' . $moduleId . '/' . $controllerId;
        $view->params['requestUrl'] = $requestUrl;


        //若站点信息失效，则直接重新登录
        if ($this->userInfo == null) {
            $currentUrl = Yii::$app->request->baseUrl . '/' . Yii::$app->request->getPathInfo();
            $expireTime = time() + Yii::getAlias('@loginCookieExpireTime');
            setcookie('requestUrl', $currentUrl, $expireTime, '/', null, null, true);
            Common::logout();
            $this->redirect(Url::to(['@userIndexLogin']));
            return;
        }
//         Yii::$app->cache->set(Yii::getAlias('@doctorWarning').$this->spotId.'_'.$this->userInfo->id,0);
        $view->params['doctorWarningCount'] = Yii::$app->cache->get(Yii::getAlias('@doctorWarning').'_'.$this->userInfo->id);


        $systemPermission = Yii::getAlias('@systemPermission');
        //如果用户拥有系统管理员－systems角色，则免验证
        if ($this->manager->checkAccess($this->userInfo->id, $systemPermission)) {
            $this->isSuperSystem = true;
            $this->getUserRole($systemPermission);
            return parent::beforeAction($action);
        }

        //允许直接访问的url
        $allowUrl = [
            Yii::getAlias('@moduleMenuSearch'),
            Yii::getAlias('@userManageEditPassword'),
            Yii::getAlias('@userManageInfo'),
            Yii::getAlias('@manageIndex'),
            Yii::getAlias('@userDefaultError')
        ];
        // 无权限可以访问的url
        if (in_array($requestUrl, $allowUrl)) {
            $this->getUserRole();
            return parent::beforeAction($action);
        }
        $userPerm = Yii::$app->cache->get(Yii::getAlias('@commonAllPerm') . $this->userInfo->id);
        if ($userPerm) {
            if (in_array($requestUrl, $userPerm)) {
                $this->getUserRole();
                return parent::beforeAction($action);
            }
        }
        if (!$this->manager->checkAccess($this->userInfo->id,$requestUrl)) {
            $this->getUserRole();
            return Common::showMessage();
        }
        $this->getUserRole();
        return parent::beforeAction($action);
    }

    //获取用户当前站点所有的权限，并渲染进layout
    private function getUserRole($role = null) {
        $datas = '';
        $list = [];
        $view = Yii::$app->view;
        $cache = Yii::$app->cache;
        $user_id = $this->userInfo->id;
        $datas = Title::getMenus($this->isSuperSystem);

        $commonAllPermCache = Yii::getAlias('@commonAllPerm') . $user_id; //普通用户全部权限列表缓存key
        if ($role != null) {
            $list = ['role' => true];
            $cache->set($commonAllPermCache,$list);
        } else {
            $dependency = new \yii\caching\DbDependency([
                'sql' => 'select count(1) from ' . AssignmentForm::tableName() . ' as a left join ' . ItemChildForm::tableName() . ' as b on a.item_name = b.parent where a.user_id = "' . $user_id .'"',
            ]);
            $commonRoleMenuCache = Yii::getAlias('@commonRoleMenu') . $user_id; //普通用户菜单url缓存key
            $commonRoleMenu = $cache->get($commonRoleMenuCache);
            $list = $cache->get($commonAllPermCache);
            if (!$commonRoleMenu || !$list) {
                $allPerms = $this->manager->getPermissionsByUser($user_id);

                //过滤字段
                if ($allPerms) {
                    foreach ($allPerms as $v) {
                            $list[] = trim($v->name); //获取用户有权限的菜单url

                    }
                    if (!empty($list)) {
                        array_unique($list);
                    }
                }

                //判断模块权限，然后是url权限

                foreach ($datas as $key => $data) {
                    // 单个url权限判断
                    foreach ($data['children'] as $subKey => $child) {
                        if(isset($child['children']) && !empty($child['children'])){
                            $thirdCount  = count($child['children']);
                            foreach ($child['children'] as $t => $thridChild){
                                $permName = $thridChild['menu_url'];

                                if (!isset($allPerms[$permName])) {
                                    $thirdCount--;
                                    unset($datas[$key]['children'][$subKey]['children'][$t]);
                                } else if ($thridChild['role_type'] == 1) {
                                    $thirdCount--;
                                    unset($datas[$key]['children'][$subKey]['children'][$t]);
                                }else{

                                    if(!isset($datas[$key]['children'][$subKey]['menuSort']) || $datas[$key]['children'][$subKey]['menuSort'] < $thridChild['titleSort']){
                                        $datas[$key]['children'][$subKey]['menu_url'] = $thridChild['menu_url'];
                                        $datas[$key]['children'][$subKey]['description'] = $child['module_description'];
                                        $datas[$key]['children'][$subKey]['menuSort'] = $thridChild['titleSort'];
                                    }
                                }
                            }
                            if($thirdCount == 0){
                                unset($datas[$key]['children'][$subKey]);
                            }
                        }else{
                            $permName = $child['menu_url'];
                            if (!isset($allPerms[$permName])) {
                                unset($datas[$key]['children'][$subKey]);
                            } else if ($child['role_type'] == 1) {
                                unset($datas[$key]['children'][$subKey]);
                            }


                        }
                    }
                }
                $cache->set($commonAllPermCache, $list, 86400, $dependency);
                $cache->set($commonRoleMenuCache, $datas, 86400, $dependency);
            } else {
                $datas = $commonRoleMenu;
            }
        }
        $view->params['defaultUrl'] = null;
        foreach ($datas as $key => $data) {
            if (count($data['children']) === 0) {
                unset($datas[$key]);
            } else {

                if ($view->params['defaultUrl'] == null) {
                    $view->params['defaultUrl'] = $data['children'][0]['menu_url'];
                }
            }
        }
        $view->params['permList'] = $list;
        $view->params['layoutData'] = $datas;
    }

    /**
     * (non-PHPdoc)
     * @property 行为日志记录
     * @see \yii\base\Controller::afterAction()
     */
    public function afterAction($action, $result) {
        // 过滤不记录掉读的接口
        if (in_array($action->id, $this->readApi)) {
            return parent::afterAction($action, $result);
        }

//         $moduleId = Yii::$app->controller->module->id;
//         $controllerId = Yii::$app->controller->id;
//         $requestUrl = '/' . $moduleId . '/' . $controllerId . '/' . $action->id;
//         $menu = Menu::findOne(['menu_url' => $requestUrl]);
//         $module = null;

//         if ($menu) {
//             $module = $menu->getTitle()->select(['module_name'])->asArray()->one();
//         }

//         $request = Yii::$app->request;
//         $getData = $request->get();
//         $bodyData = $request->getBodyParams();
//         $postData = $request->post();
//         if ($request->isPost) {

//             if ($module && (count($getData) > 0 || count($bodyData) > 0)) {
//                 $data = Json::encode(array(
//                             'GET' => $getData,
//                             'BODY' => $bodyData,
//                 ));
//                 BehaviorRecord::log(
//                         $this->userInfo->id, $request->userIP, $this->spotId, $module['module_name'], $requestUrl, $data
//                 );
//             }
//         }
        return parent::afterAction($action, $result);
    }


}
