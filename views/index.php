<?php

use pvsaintpe\search\helpers\Html;
use pvsaintpe\grid\widgets\GridView;

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
            'id' => 'wlog',
            'bordered' => false,
            'clickable' => false,
            'pager' => [
                'class' => '\yii\widgets\LinkPager',
            ],
            'dataProvider' => $dataProvider,
            'filterModel' => null, //$searchModel,
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
if (in_array($searchModel->attribute, $searchModel->getLogStatusAttributes())) {
    $jsCode = <<<JS
(function ($) {
    $('.rollback-button').click(function (e) {
        e.preventDefault();
        var labelId = 'label-' + $(this).attr('id');
        var dataVal = $(this).attr('data-value');
        $('#' + labelId).parent().parent().parent().parent().find('input').each(function(index, element) {
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
        $('#' + labelId).parent().parent().parent().parent().find('select').val($(this).attr('data-value'));
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
        $('#' + labelId).parent().parent().parent().parent().find('input').val($(this).attr('data-value'));
        $('#' + labelId).parent().parent().parent().parent().find('select').val($(this).attr('data-value')).trigger('change');
        
        var pattern = /^\d+(,\d+)*$/;
        if (pattern.test($(this).attr('data-value'))) {
            var select2 = $('#' + labelId).parent().parent().parent().parent().find('.select2');
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
}

$this->registerJs($jsCode);
?>