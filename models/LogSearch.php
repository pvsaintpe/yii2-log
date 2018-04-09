<?php

namespace backend\modules\changelog\models;

use backend\helpers\Html;
use backend\traits\PerformanceTrait;
use backend\traits\SearchTrait;
use backend\components\SearchInterface;
use backend\components\PerformanceInterface;
use common\components\ActiveQuery;
use common\components\ActiveRecord;
use common\models\log\StorePaymentSystemMethodCurrencySettingsLog;
use Yii;
use yii\data\ActiveDataProvider;

/**
 * Class LogSearch
 * @package backend\modules\changelog\models\base
 */
class LogSearch extends ActiveRecord implements SearchInterface, PerformanceInterface
{
    use SearchTrait;
    use PerformanceTrait;

    /** @var string */
    public $attribute;

    /** @var string */
    public $route;

    /** @var string */
    public $hash;

    /** @var string */
    public $where;

    /** @var string */
    public $search_class_name;

    /**
     * @return int
     */
    protected function getPaginationSize()
    {
        return 10;
    }

    /**
     * Заголовок GridView
     * @return mixed
     */
    public static function getGridTitle()
    {
        return Yii::t('log', 'История изменений');
    }

    /**
     * @return array
     */
    public function getGridColumns()
    {
        return [
            'log_id' => [
                'class' => 'backend\components\grid\IdColumn',
                'attribute' => 'log_id',
                'label' => 'ID',
            ],
            'value' => [
                'class' => 'backend\components\grid\DataColumn',
                'attribute' => $this->attribute,
                'label' => $this->getAttributeLabel('value')
            ],
            'updated_by' => [
                'class' => 'backend\components\grid\UpdatedByColumn',
                'customFilters' => $this->getFilter('updated_by'),
                'allowNotSet' => true,
                'value' => function($model) {
                    return $model->updatedBy ? $model->updatedBy->username : null;
                }
            ],
            'timestamp' => [
                'class' => 'backend\components\grid\TimestampColumn',
            ],
            'actions' => [
                'class' => 'backend\components\grid\DataColumn',
                'header' => Yii::t('payment','Действия'),
                'vAlign' => 'middle',
                'format' => 'raw',
                'width' => '100px',
                'value' => function (ActiveRecord $model, $index, $key, $widget) {
                    $buttons[] = Html::button(
                        Yii::t('models', 'Вернуть значение'),
                        [
                            'class' => 'btn btn-success btn-xs rollback-button',
                            'data-pjax' => 0,
                            'data-value' => $model->{$this->attribute},
                            'id' => $this->hash,
                        ]
                    );

                    return join(' &nbsp; ', $buttons);
                }
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(
            [
                [['attribute', 'route', 'search_class_name', 'where', 'hash'], 'required'],
            ],
            parent::rules()
        );
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'attribute' => Yii::t('log', 'Настройка'),
                'value' => Yii::t('log', 'Значение'),
                'timestamp' => Yii::t('log', 'Метка времени'),
                'updated_by' => Yii::t('log', 'Кем обновлено'),
            ]
        );
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params = null)
    {
        if (!empty($params)) {
            $this->load($params);
        }

        /** @var ActiveRecord|SearchTrait $searchClass */
        $searchClass = $this->search_class_name;

        /** @var ActiveQuery query */
        $this->query = $searchClass::find();
        $this->query->innerJoin('admin admin', 'admin.id = ' . $this->query->a('updated_by'));

        $this->query->select([
            $this->query->a('log_id'),
            $this->query->a('updated_by'),
            $this->query->a('timestamp'),
            $this->query->a($this->attribute),
        ]);

        $this->query->andWhere([
            'NOT',
            [
                $this->query->a($this->attribute) => null,
            ],
        ]);

        if ($this->where) {
            $this->query->andWhere(@unserialize($this->where));
        }

        return $this->getDataProvider();
    }
}