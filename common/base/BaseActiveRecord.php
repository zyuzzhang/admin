<?php

namespace app\common\base;

use yii\db\ActiveRecord;
use Yii;

class BaseActiveRecord extends ActiveRecord {

    public $userInfo; //用户的user信息;

    public function init() {
        parent::init();
        $this->userInfo = Yii::$app->user->identity;

    }

    public function beforeSave($insert) {
        //判断当前表中是否相应更新时间字段
        if ($insert) {
            $this->create_time = time();
        }
        $this->update_time = time();
        return parent::beforeSave($insert);
    }
    public static $getStatus = [
        '1' => '正常',
        '2' => '停用'
    ];


}
