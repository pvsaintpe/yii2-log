<?php

namespace pvsaintpe\log\components\grid;

use pvsaintpe\search\helpers\Html;
use pvsaintpe\search\components\ActiveRecord;
use Yii;

/**
 * Class IdColumn
 * @package pvsaintpe\log\components\grid
 */
class IdColumn extends DataColumn
{
    public $width = '36px';

    public $attribute = 'id';

    /**
     * @var string
     */
    public $permissionPrefix;

    /**
     * @var string
     */
    public $linkAction = 'view';

    /**
     * @var array
     */
    public $linkOptions = [];

    /**
     * @var string|array
     */
    public $baseCssClass;

    /**
     * @var bool
     */
    public $disablePjax = true;


    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->baseCssClass) {
            Html::addCssClass($this->linkOptions, $this->baseCssClass);
        }
        if ($this->disablePjax) {
            $this->linkOptions['data-pjax'] = '0';
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $content = parent::renderDataCellContent($model, $key, $index);

        if ($this->linkAction && $this->checkAccess($model)) {
            $url = array_merge((array)$this->linkAction, $model->getPrimaryKey(true));
            $content = Html::a($content, $url, $this->linkOptions);
            if ($this->disablePjax) { // hack
                $content = '<span></span>' . $content;
            }
        }
        return $content;
    }

    /**
     * @param ActiveRecord $model
     * @return bool
     */
    protected function checkAccess(ActiveRecord $model)
    {
        if ($this->permissionPrefix) {
            $canParams = $model->getPrimaryKey(true);
            $canParams['model'] = $model;
            return Yii::$app->getUser()->can($this->permissionPrefix . $this->linkAction, $canParams);
        } else {
            return false;
        }
    }
}
