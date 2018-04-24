<?php

namespace app\modules\user\controllers;

use yii\web\Controller;

class DefaultController extends Controller
{

    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex() {


        return $this->render('index');
    }

}
