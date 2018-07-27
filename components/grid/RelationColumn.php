<?php

namespace pvsaintpe\log\components\grid;

use pvsaintpe\search\helpers\Html;
use pvsaintpe\search\components\ActiveRecord;
use pvsaintpe\search\interfaces\MessageInterface;
use Yii;

/**
 * Class RelationColumn
 * @package pvsaintpe\log\components\grid
 */
class RelationColumn extends Select2Column
{
    /**
     * @var string
     */
    public $permissionName;

    /**
     * @var array|string|null
     */
    public $linkUrl = null;

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
        if (is_null($this->query)) {
            $filterModel = $this->grid->filterModel;
            if ($filterModel instanceof ActiveRecord) {
                //$this->allowNotSet = $filterModel::getTableSchema()->getColumn($this->attribute)->allowNull;
                foreach ($filterModel::singularRelations() as $relationName => $relationData) {
                    if (isset($relationData['link']['id']) && $this->attribute == $relationData['link']['id']) {
                        /* @var $relationClass \yii\db\ActiveRecordInterface|string */
                        $relationClass = $relationData['class'];
                        if (is_subclass_of($relationClass, ActiveRecord::class)) {
                            $this->query = $relationClass::find();
                            $this->relationClass = $relationClass;
                        }
                    }
                }

                //@fix (для relation с составными ключами)
                if (!$this->relationClass) {
                    foreach ($filterModel::singularRelations() as $relationName => $relationData) {
                        if (in_array($this->attribute, $relationData['link']) && count($relationData['link']) == 1) {
                            /* @var $relationClass \yii\db\ActiveRecordInterface|string */
                            $relationClass = $relationData['class'];
                            if (is_subclass_of($relationClass, ActiveRecord::class)) {
                                $this->query = $relationClass::find();
                                $this->relationClass = $relationClass;
                            }
                        }
                    }
                }
            }
            if (!$this->query && ($relationClass = $this->relationClass)) {
                $this->query = $relationClass::find();
            }
        }
        parent::init();
    }

    /**
     * @param ActiveRecord $model
     * @return ActiveRecord
     */
    protected function getRelatedModel(ActiveRecord $model)
    {
        foreach ($model::singularRelations() as $relationName => $relationData) {
            if (in_array($this->attribute, $relationData['link']) && $model->isRelationPopulated($relationName)) {
                $relatedModel = $model->{$relationName};
                if ($relatedModel instanceof ActiveRecord) {
                    return $relatedModel;
                }
            }
        }
        if ($this->relationMethod) {
            $methodName = $this->relationMethod;
            return $model->$methodName()->one();
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getDataCellValue($model, $key, $index)
    {
        if ($model instanceof ActiveRecord) {
            $relatedModel = $this->getRelatedModel($model);
            if ($relatedModel) {
                if ($relatedModel instanceof MessageInterface) {
                    return $relatedModel->getDocName();
                } else {
                    return $relatedModel->getTitleText();
                }
            }
        }
        return parent::getDataCellValue($model, $key, $index);
    }

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $content = parent::renderDataCellContent($model, $key, $index);
        if ($this->linkUrl && ($model instanceof ActiveRecord)) {
            $relatedModel = $this->getRelatedModel($model);
            if ($relatedModel && $this->checkAccess($relatedModel)) {
                $url = array_merge((array)$this->linkUrl, $relatedModel->getPrimaryKey(true));
                $content = Html::a($content, $url, $this->linkOptions);
                if ($this->disablePjax) { // hack
                    $content = '<span></span>' . $content;
                }
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
        if ($this->permissionName || $this->linkUrl) {
            if ($this->permissionName) {
                $permissionName = $this->permissionName;
            } else {
                $permissionName = ltrim($this->linkUrl, '/');
            }
            $canParams = $model->getPrimaryKey(true);
            $canParams['model'] = $model;
            return Yii::$app->getUser()->can($permissionName, $canParams);
        } else {
            return false;
        }
    }
}
