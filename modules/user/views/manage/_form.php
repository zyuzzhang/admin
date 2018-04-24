<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use dosamigos\datepicker\DatePicker;
use app\modules\user\models\User;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\modules\user\models\User */
/* @var $form yii\widgets\ActiveForm */
$attribute = $model->attributeLabels();
$baseUrl = Yii::$app->request->baseUrl;
$public_img_path = $baseUrl . '/public/img/';

?>

<div class="user-form">

    <?php $form = ActiveForm::begin([
        'method' => 'post',
        'options' => ['enctype' => 'multipart/form-data'],
    ]); ?>
    <div class='col-sm-2 col-md-2'>
        <?= $form->field($model, 'head_img')->hiddenInput(['id' => 'avatar_url'])->label(false); ?>
        <div id="crop-avatar">
            <!-- Current avatar -->
            <div class="avatar-view" title="上传头像">
                <?php if ($model->head_img): ?>
                    <?= Html::img(Yii::$app->params['cdnHost'] . $model->head_img, ['alt' => '头像', 'onerror' => "this.src='{$public_img_path}default.png'"]) ?>
                <?php else: ?>
                    <?= Html::img(Yii::$app->request->baseUrl . '/public/img/user/img_user_big.png', ['alt' => '头像']) ?>
                <?php endif; ?>
                <div class='btn btn-default font-body2 header_img'>上传头像</div>
            </div>

        </div>

    </div>
    <div class='col-sm-10 col-md-10'>
        <div class='row'>
            <div class='col-sm-4'>
                <?= $form->field($model, 'username')->textInput(['maxlength' => true])->label($attribute['username'] . '<span class = "label-required">*</span>') ?>
            </div>

            <div class='col-sm-4'>
                <?= $form->field($model, 'sex')->radioList(User::$getSex)->label($attribute['sex'] . '<span class = "label-required">*</span>') ?>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-4'>
                <?= $form->field($model, 'iphone')->textInput(['maxlength' => true])->label($attribute['iphone'] . '<span class = "label-required">*</span><span style="color:#FF4B00;">&nbsp（登录账号）</span>') ?>
            </div>
            <div class='col-sm-4'>
                <?= $form->field($model, 'email')->textInput(['maxlength' => true])->label($attribute['email'] . '<span class = "label-required">*</span><span style="color:#FF4B00;">&nbsp（登录账号）</span>') ?>
            </div>

        </div>
        <div class='row form-margin'>
            <div class='col-sm-4'>
                <?= $form->field($model, 'occupation')->dropDownList(User::$getOccuption, ['prompt' => '请选择'])->label($attribute['occupation'] . '<span class = "label-required">*</span>') ?>
            </div>
             <div class='col-sm-4'>
                <?= $form->field($model, 'status')->dropDownList(User::$getStatus) ?>
            </div>

        </div>

        <div class='row'>
            <div class='col-sm-12'>
                <?= $form->field($model, 'role')->checkboxList(ArrayHelper::map($roleInfo, 'name', 'description')); ?>

                <?= $form->field($model, 'introduce')->textarea(['rows' => 5]) ?>
            </div>
        </div>
        <div class="form-group">
            <?= Html::a('取消', ['index'], ['class' => 'btn btn-cancel btn-form second-cancel']) ?>
            <?= Html::submitButton('保存', ['class' => 'btn btn-default btn-form']) ?>
        </div>


    </div>

    <?php ActiveForm::end(); ?>

</div>
