<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

echo "<?php\n";
?>

use yii\helpers\Html;
use app\common\AutoLayout;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

$this->title = <?= $generator->generateString('新增'.$generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))), ['modelClass' => Inflector::camel2words(StringHelper::basename($generator->modelClass))]) ?>;
$this->params['breadcrumbs'][] = ['label' => <?= $generator->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($generator->modelClass)))) ?>, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$baseUrl = Yii::$app->request->baseUrl;
?>
<?= "<?php "?> AutoLayout::begin(['viewFile' => '@app/views/layouts/layout.php'])?>
<?= "<?php "?> $this->beginBlock('renderCss')?>

<?= "<?php "?> $this->endBlock();?>
<?= "<?php "?> $this->beginBlock('content')?>
<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-create col-xs-12">
    <div class = "box">
    <div class="box-header with-border">
      <span class = 'left-title'><?= "<?= " ?>Html::encode($this->title) ?></span>
      <?= "<?= " ?> Html::a(Html::img($baseUrl.'/public/img/common/icon_back.png').'返回',['index'],['class' => 'right-cancel second-cancel','data-pjax' => 0]) ?>      
    </div>
        <div class = "box-body">    

            <?= "<?= " ?>$this->render('_form', [
                'model' => $model,
            ]) ?>
        </div>
    </div>
</div>
<?= "<?php "?> $this->endBlock()?>
<?= "<?php "?> $this->beginBlock('renderJs')?>

<?= "<?php "?> $this->endBlock()?>
<?= "<?php "?> AutoLayout::end()?>