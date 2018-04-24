<?php

namespace app\modules\user\models;

use Yii;
use app\common\base\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;
use app\modules\user\models\User;
use app\modules\_set\models\SecondDepartment;
use yii\base\Object;
use yii\db\Query;
use yii\helpers\Json;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use app\modules\_set\models\SecondDepartmentUnion;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property integer $id
 * @property string $username 用户名
 * @property string $email 邮箱
 * @property string $card 身份证号
 * @property string $head_img 头像
 * @property string $introduce 个人介绍
 * @property string $auth_key
 * @property string $password_hash 密码
 * @property string $password_reset_token 重置密码token
 * @property string $role
 * @property integer $_id 机构id
 * @property integer $default_ 默认诊所id
 * @property integer $status 状态
 * @property integer $create_time 创建时间
 * @property integer $update_time 更新时间
 * @property integer $sex 性别
 * @property integer $iphone 手机号码
 * @property integer $occupation 职位
 * @property integer $occupation_type 职位性质(1-全职,2-半全职,3-兼职)
 * @property integer $position_title 职称
 * @property date $birthday 出生日期
 * @property string  $expire_time 重置密码token有效期
 * @property AuthAssignment[] $authAssignments
 */
class User extends BaseActiveRecord implements IdentityInterface
{

    public $password; //重置密码
    public $reType_password; //确认重置密码
    public $oldPassword; //修改密码－旧密码
    public $clinic_id; //诊所
    public $department; //科室
    public $role; //角色
    public $code; //验证码
    public $code_btn; //验证码
    public $code_error;
    public $clinic_name;//所属诊所名称
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['code', 'oldPassword', 'password_hash', 'password_reset_token', '_id', 'username', 'email', 'password', 'reType_password', 'sex', 'iphone', 'occupation'], 'required'],
            ['reType_password', 'compare', 'compareAttribute' => 'password', 'message' => '与第一次密码输入不符合,请重新输入'],
            ['email', 'email'],
            [['id', 'code', 'status', 'create_time', 'update_time', 'occupation', 'occupation_type', 'position_title'], 'integer'],
            [['username', 'auth_key', 'head_img'], 'string', 'max' => 64],
            [['password_hash', 'password_reset_token', 'introduce'], 'string', 'max' => 255],
            [['iphone'], 'match', 'pattern' => '/^\d{11}$/'],
            [['expire_time'], 'safe'],
            [['username', 'email', 'introduce'], 'trim'],
            [['iphone', 'email'], 'validateIphoneEmail', 'on' => 'register'],
            ['oldPassword', 'validateOldPassword'],
            ['code', 'validatecode', 'on' => 'resetPassword'],
            ['password', 'match', 'pattern' => '/(?!^[0-9]+$)(?!^[A-z]+$)(?!^[^A-z0-9]+$)^[^\s]{8,20}$/', 'message' => '密码不符合要求'],
        ];
    }

    public function scenarios() {

        $parent = parent::scenarios();
        $parent['login'] = ['username', 'password'];
        $parent['register'] = ['username', 'email', 'sex', 'iphone', 'occupation', 'introduce', 'head_img', 'role', 'status'];
        $parent['resetPassword'] = ['password_reset_token', 'password', 'reType_password', 'code']; //重置密码场景
        $parent['resetSave'] = ['expire_time', 'id'];
        $parent['editPassword'] = ['password', 'oldPassword', 'reType_password']; //修改密码
//         $parent['registerSystem'] = ['username', 'email', 'iphone', '_id']; //添加机构管理员人员信息场景
        $parent['delete'] = ['username', 'status']; //删除场景
        return $parent;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'username' => '用户名',
            'email' => '邮箱',
            'auth_key' => 'Auth_Key',
            'oldPassword' => '旧密码',
            'password_hash' => '密码',
            'password' => '新密码',
            'reType_password' => '确认新密码',
            'password_reset_token' => 'password_reset_token',
            'status' => '状态',
            'iphone' => '手机号码',
            'occupation' => '职位',
            'introduce' => '个人介绍',
            'head_img' => '头像',
            'sex' => '性别',
            'role' => '角色',
            'code' => '验证码',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'pushpassword' => '输入密码',
            'surepassword' => '确认密码',
            'iphone_code' => '手机验证码',
        ];
    }

    /**
     * @var 性别
     */
    public static $getSex = [
        1 => '男',
        2 => '女',
//        3 => '不详',
//        4 => '其他',
    ];




    /**
     * @var 职位
     */
    public static $getOccuption = [
        1 => '管理员',
        4 => '前台',

        8 => '行政',
        9 => '其他',
    ];
    public static $getStatus = [
        1 => '正常',
        2 => '停用',
    ];
    public static $getDepartment = [
        1 => '儿保科',
        2 => '全科',
    ];

    /**
     *
     * @param unknown $attribute 字段属性
     * @param unknown $params
     */
    public function validateOldPassword($attribute, $params) {
        if (!$this->hasErrors()) {
            $user = self::findIdentity(self::getId());

            if (!self::validatePassword($this->oldPassword, $user->password_hash)) {
                $this->addError($attribute, '密码错误.');
            }
        }
    }




    public function validateIphoneEmail($attribute, $params) {
        if (!$this->hasErrors()) {
            if ($this->isNewRecord) {
//                $hasRecord = User::find()->select(['id'])->where(['_id' => $_COOKIE['parentId'], $attribute => $this->$attribute])->asArray()->limit(1)->one();
                //检测手机号是否存在
                $hasIphoneRecord = $this->checkDuplicate('iphone', $this->iphone);
                if ($hasIphoneRecord) {
                    $this->addError('iphone', $this->attributeLabels()['iphone'] . '已经被占用');
                }
                //检测email是否存在
                $hasEmailRecord = $this->checkDuplicate('email', $this->email);
                if ($hasEmailRecord) {
                    $this->addError('email', $this->attributeLabels()['email'] . '已经被占用');
                }
            } else {
                $oldIphone = $this->getOldAttribute('iphone');
                $oldEmail = $this->getOldAttribute('email');
                if ($oldIphone != $this->iphone) {
                    $hasRecord = $this->checkDuplicate('iphone', $this->iphone);
                    if ($hasRecord) {
                        $this->addError('iphone', $this->attributeLabels()['iphone'] . '已经被占用');
                    }
                }
                if ($oldEmail != $this->email) {
                    $hasRecord = $this->checkDuplicate('email', $this->email);
                    if ($hasRecord) {
                        $this->addError('email', $this->attributeLabels()['email'] . '已经被占用');
                    }
                }
            }
        }
    }



    /**
     * 根据给到的ID查询身份。
     *
     * @param string|integer $id 被查询的ID
     * @return IdentityInterface|null 通过ID匹配到的身份对象
     */
    public static function findIdentity($id) {
        return static::find()->select(['id', 'username', 'email', 'password_hash', 'auth_key', 'head_img'])->where(['id' => $id])->one();
    }

    /**
     * 根据 token 查询身份。
     *
     * @param string $token 被查询的 token
     * @return IdentityInterface|null 通过 token 得到的身份对象
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        return null;
        //return static::findOne(['password_reset_token' => $token]);
    }

    public static function findByUsername($username) {
        return static::findOne(['username' => $username]);
    }

    public static function findByEmail($email) {
        $num = substr_count($email, '@');
        if ($num == 1) {
            $condition['email'] = $email;
        } else {
            $condition['iphone'] = $email;
        }



        return static::find()->select(['id', 'username', 'email', 'password_hash', 'auth_key', 'status'])->where($condition)->andWhere('status != 3')->one();
    }

    /**
     * @return int|string 当前用户ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     *
     * @param string $user_id 默认为email值
     *
     */
    // public function generateUserId($user_id = null){
    //     $this->user_id = $user_id?$user_id:$this->email;
    // }
    /**
     * @return string 当前用户的（cookie）认证密钥
     */
    public function getAuthKey() {
        return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword($password, $password_hash) {
        return Yii::$app->getSecurity()->validatePassword($password, $password_hash);
    }

    public function generatePasswordHash() {
        $this->password_hash = Yii::$app->getSecurity()->generatePasswordHash($this->password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {
        $this->auth_key = Yii::$app->getSecurity()->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->password_reset_token = Yii::$app->getSecurity()->generateRandomString() . '_' . time();
    }

    /**
     * Generates new code
     */
    public function generateCode() {
        $this->code = Yii::$app->getSecurity()->generateRandomString(16) . $this->email . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->password_reset_token = null;
    }



    public function beforeSave($insert) {
        if ($insert) {
            if ($this->isNewRecord) {

                // $this->generateUserId($this->email);
                $this->generateAuthKey();
                $this->generatePasswordResetToken();
//                 $this->generateCode();
            }
            if (!empty($this->password)) {
                $this->generatePasswordHash();
            }
        }
        if (!$insert && !empty($this->password)) {
            $this->generatePasswordHash();
        }

        return parent::beforeSave($insert);
    }

    /**
     *
     * 发送注册邮件验证
     */
    public function sendRegisterMail($data, $Info = null, $emailFile = null) {
        if (empty($emailFile)) {
            $emailFile = Yii::getAlias('@registerEmail');
        }
        if (!empty($Info)) {
            $parentName = $Info['parentName'];
            $parentCode = $Info['parentCode'];
        } else {
            $parentName = Yii::$app->cache->get(Yii::getAlias('@parentName') . $this->Id . $this->userInfo->id);
            $parentCode = Yii::$app->cache->get(Yii::getAlias('@parentCode') . $this->Id . $this->userInfo->id);

//             $parentName = $_COOKIE['parentName'];
//             $parentCode = $_COOKIE['parentCode'];
        }
        $model = User::find()->select(['id', 'expire_time'])->where(['id' => $data->id])->one();
        $model->scenario = 'resetSave';
        $model->expire_time = time() + 86400;
        if ($model->save()) {

            $mail = Yii::$app->mailer->compose($emailFile, ['data' => $data, 'parentName' => $parentName, 'parentCode' => $parentCode]);
            $mail->setTo($data->email);
            $mail->setSubject("欢迎加入" . $parentName);
            //邮件发送成功后，重置expire_time
            if ($mail->send()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 用户与诊所关联
     */
    public function getUser() {
        return $this->hasMany(User::className(), ['user_id' => 'id']);
    }

    /**
     * @return user_id,username
     * @return \yii\db\ActiveQuery
     */
    public static function getUserData() {
        return self::find()->select(['email', 'username'])->asArray()->all();
    }








    /**
     * @param 诊所id $_id
     * @return 诊所的用户的id和username
     */
    public static function getUserList($_id) {
        $query = new Query();
        $query->from(['a' => User::tableName()]);
        $query->select(['id' => 'a.user_id', 'b.username']);
        $query->leftJoin(['b' => self::tableName()], '{{a}}.user_id = {{b}}.id');
        $query->where(['a._id' => $_id, 'b.status' => 1]);
        return $query->all();
    }

    public function validatecode($attribute, $params) {
        if (!$this->hasErrors()) {
            $hasRecord = Code::find()->select(['id', 'expire_time', 'code'])->where(['_id' => $this->_id, 'iphone' => $this->iphone, 'user_id' => $this->id, 'type' => 1])->orderBy('id DESC')->asArray()->one();
            if ($hasRecord) {
                if (time() > $hasRecord['expire_time']) {
                    $this->code = '';
                    $this->addError($attribute, '验证码失效，请重新获取验证码');
                } else if ($hasRecord['code'] != $this->$attribute) {
                    $this->code = '';
                    $this->addError($attribute, '验证码错误，请重新输入验证码');
                }
            } else {
                $this->code = '';
                $this->addError($attribute, '验证码错误,请重新输入验证码');
            }
        }
    }



    /**
     * @param $userId 用户id
     * @param $field 要查询的用户字段信息  默认为全部信息
     * @return 用户的信息
     */
    public static function getUserInfo($userId, $field = null) {
        if ($field == null) {
            $field = '*';
        }
        return self::find()->select($field)->where(['id' => $userId])->asArray()->one();
    }





}
