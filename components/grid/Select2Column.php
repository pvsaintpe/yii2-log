<?php

namespace pvsaintpe\log\components\grid;

use pvsaintpe\search\widgets\GridView;
use pvsaintpe\search\components\ActiveRecord;
use pvsaintpe\search\components\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\base\Model;
use Yii;

/**
 * Class Select2Column
 * @package pvsaintpe\log\components\grid
 */
class Select2Column extends DataColumn
{
    const DEFAULT_NOT_SET_VALUE = -1;

    public $format = 'raw';

    public $filterType = GridView::FILTER_SELECT2;

    public $filterWidgetOptions = [
        'pluginOptions' => ['allowClear' => true]
    ];

    /**
     * @var []
     */
    public $customFilters = [];

    /**
     * @var ActiveQuery
     */
    public $query;

    /**
     * @var bool
     */
    public $useGlobalFilter = true;

    /**
     * @var bool
     */
    public $allowNotSet = true;

    /**
     * @var int|string
     */
    public $notSetValue = self::DEFAULT_NOT_SET_VALUE;

    /**
     * @var string
     */
    public $notSetText;

    /**
     * @var bool
     */
    public $translate = false;

    /**
     * @var bool
     */
    public $indexByName = false;

    /**
     * @var ActiveRecord
     */
    public $relationClass;

    public $relationMethod;

    public $debug = false;

    public function updateQuery()
    {
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $filterModel = $this->grid->filterModel;

        if ($filterModel instanceof Model) {
            $this->filterInputOptions['placeholder'] = ($this->header ?: $this->label)
                ?: $filterModel->getAttributeLabel($this->attribute);
        }
        if ($this->query instanceof ActiveQuery) {
            $this->updateQuery();
            if ($this->useGlobalFilter) {
                $this->query->globalFilter($this->customFilters);
            }
            if ($this->translate && !$this->filter) {
                $listItems = $this->query->listItems($this->indexByName)->column();
                $queryClass = $this->relationClass;
                foreach ($listItems as $index => $value) {
                    $query = $queryClass::find();
                    $obj = !$this->indexByName
                        ? $query->id($index)->one()
                        : $query->andWhere(['name' => $index])->one();

                    $listItems[$index] = is_object($obj) ? $obj->getDocName() : Yii::t('const', $value);
                }
                $this->filter = $listItems;
            }
            if (is_null($this->filter)) {
                $this->filter = $this->query->listItems()->column();
            }
        }
        if ($this->allowNotSet) {
            if (is_null($this->notSetText)) {
                $this->notSetText = Yii::t('backend', 'Не задано');
            }
            if (is_array($this->filter)) {
                $this->filter = ArrayHelper::merge([$this->notSetValue => $this->notSetText], $this->filter);
            }
        }

        parent::init();
    }

    /**
     * @param \yii\db\QueryInterface $query
     * @param array $columns attribute => value
     */
    public static function modifyQuery($query, array $columns)
    {
        foreach ($columns as $attribute => $value) {
            if ($value == static::DEFAULT_NOT_SET_VALUE) {
                $query->andWhere([$attribute => null]);
            } else {
                $query->andFilterWhere([$attribute => $value]);
            }
        }
    }
}
