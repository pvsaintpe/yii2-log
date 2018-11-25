<?php

use pvsaintpe\grid\widgets\GridView;
use pvsaintpe\log\components\Configs;
use yii\helpers\Url;
use yii\data\Sort;

/* @var $this yii\web\View */
/* @var $searchModel \pvsaintpe\search\interfaces\SearchInterface|\pvsaintpe\log\models\ChangeLogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $permissionPrefix */

$this->title = $searchModel::getGridTitle();
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box box-danger log-index">
    <div class="box-body">
        <?= GridView::widget([
            'bordered' => false,
            'clickable' => false,
            'pager' => ['class' => '\yii\widgets\LinkPager'],
            'id' => uniqid('wlog'),
            'pjax' => true,
            'pjaxSettings' => [
                'options' => [
                    'enablePushState' => false,
                    'enableReplaceState' => false,
                ],
            ],
            'filterUrl' => Url::to([Configs::instance()->pathToRoute]),
            'sorter' =>  [
                'class' => 'yii\widgets\LinkSorter',
                'sort'  => new Sort([
                    'route' =>  Url::to([Configs::instance()->pathToRoute])
                ]),
                'options' => [
                    'data-method' => 'POST',
                    'data-pjax' => 1,
                ]
            ],
            'dataProvider' => $dataProvider,
            'filterModel' => null,
            'disableColumns' => $searchModel->getDisableColumns(),
            'columns' => $searchModel->getGridColumns(),
            'toolbar' => $searchModel->getGridToolbar(),
            'panelTemplate' => '<div class="{prefix}{type}">
    {panelHeading}
    {items}
    {panelFooter}
</div>',
        ]) ?>
    </div>
</div>

<?php
if (in_array($searchModel->attribute, $searchModel::getBooleanAttributes())) {
    $jsCode = <<<JS
(function ($) {
    $('.rollback-button').click(function (e) {
        e.preventDefault();
        var labelId = 'label-' + $(this).attr('id');
        var dataVal = $(this).attr('data-value');
        $('#' + labelId).closest('label').parent().find('input').each(function(index, element) {
            if ($(element).attr('type') === 'checkbox') {
                if (dataVal == 0) {
                    $(element).removeAttr('checked');
                    $(element).trigger('change');
                } else {
                    $(element).attr('checked', 'checked');
                    $(element).trigger('change');
                } 
            }
        });
        $('#' + labelId).closest('label').parent().find('select').val($(this).attr('data-value'));
        $('#main-modal').modal('hide');
    })
})(jQuery);
JS
    ;
} elseif (in_array($searchModel->attribute, $searchModel::getRelationAttributes())) {
    $jsCode = <<<JS
(function ($) {
    $('.rollback-button').click(function (e) {
        e.preventDefault();
        var labelId = 'label-' + $(this).attr('id');
        $('#' + labelId).closest('label').parent().find('select').val($(this).attr('data-value')).trigger('change');
        var pattern = /^\d+(,\d+)*$/;
        if (pattern.test($(this).attr('data-value'))) {
            var select2 = $('#' + labelId).closest('label').parent().find('.select2');
            if (select2) {
                var selectedValues = $(this).attr('data-value').split(',');
                select2.select2('val', selectedValues);
            }
        }
        $('#main-modal').modal('hide');
    })
})(jQuery);
JS
    ;
} else {
    $jsCode = <<<JS
(function ($) {
    $('.rollback-button').click(function (e) {
        e.preventDefault();
        var labelId = 'label-' + $(this).attr('id');
        $('#' + labelId).closest('label').parent().find('input').val($(this).attr('data-value'));
        $('#main-modal').modal('hide');
    })
})(jQuery);
JS
    ;
}

$this->registerJs($jsCode);
?>