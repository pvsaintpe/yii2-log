<?php

use pvsaintpe\search\helpers\Html;
use pvsaintpe\search\widgets\GridView;

/* @var $this yii\web\View */
/* @var $searchModel \pvsaintpe\search\interfaces\SearchInterface */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $permissionPrefix */

$this->title = $searchModel::getGridTitle();
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box box-danger log-index">
    <div class="box-body">
        <?= GridView::widget([
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
    $this->registerJs(<<<JS
(function ($) {
    $('.rollback-button').click(function (e) {
        e.preventDefault();
        var labelId = 'label-' + $(this).attr('id');
        $('#' + labelId).parent().parent().parent().find('input').val($(this).attr('data-value'));
        $('#main-modal').modal('hide');
    })
})(jQuery);
JS
);
?>