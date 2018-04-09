<?php

/** @var array $columns */
/** @var string $className */
/** @var string $modelName */
/** @var string $modelNamespace */

echo "<?php" ?>

namespace backend\modules\changelog\models\base;

use backend\traits\PerformanceTrait;
use backend\traits\SearchTrait;
use backend\components\SearchInterface;
use backend\components\PerformanceInterface;
use Yii;
use yii\data\ActiveDataProvider;
use <?= $modelNamespace . $modelName ?>

/**
 * Class LogSearch
 * @package backend\modules\changelog\models\base
 */
class <?= $className?> extends <?= $modelName ?> implements SearchInterface, PerformanceInterface
{
    use SearchTrait;
    use PerformanceTrait;

    /** @var string */
    public $attribute;

    /** @var string */
    public $route;

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
                'value' => function (StorePaymentSystemMethodCurrencySettingsLog $model, $index, $key, $widget) {
                    $buttons[] = Html::a(
                        Yii::t('models', 'Вернуть значение'),
                        [
                            'rollback',
                            'id' => $model->log_id,
                            'route' => $model->route,
                            'search_class_name' => $model->search_class_name,
                        ],
                        [
                            'class' => 'btn btn-success btn-xs',
                            'data-pjax' => 0,
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
                [['attribute', 'route', 'search_class_name'], 'save'],
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
                'attribute' => 'Настройка',
                'value' => 'Значение',
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

        $this->query = static::find();
        $this->query->innerJoinWith('updatedBy');

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

        <?php
            foreach ($columns as $column) {
                echo "\t\t\$this->query->andFilterWhere([\$this->query->a('{$column}') => \$this->{$column}]);\n";
            }
        ?>

        return $this->getDataProvider();
    }
}