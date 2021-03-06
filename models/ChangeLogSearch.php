<?php

namespace pvsaintpe\log\models;

use pvsaintpe\helpers\Url;
use pvsaintpe\log\components\Configs;
use pvsaintpe\log\models\base\ChangeLogSearchBase;
use pvsaintpe\search\helpers\Html;
use pvsaintpe\search\components\ActiveQuery;
use pvsaintpe\search\interfaces\SearchInterface;
use Yii;
use yii\db\Query;

/**
 * Class ChangeLogSearch
 * @package pvsaintpe\logs\models
 */
class ChangeLogSearch extends ChangeLogSearchBase implements SearchInterface
{
    /**
     * @var string
     */
    public $attribute;

    /**
     * @var string
     */
    public $route;

    /**
     * @var string
     */
    public $hash;

    /**
     * @var string
     */
    public $where;

    /**
     * @var string
     */
    public $table;

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'attribute',
            'route',
            'hash',
            'where',
            'table'
        ];
    }

    public function init()
    {
        parent::init();
        $this->defaultPageSize = Configs::instance()->defaultPageSize;
    }

    /**
     * @return array
     */
    public function safeAttributes()
    {
        return [
            'attribute',
            'route',
            'hash',
            'where',
            'table'
        ];
    }

    /**
     * @return array|mixed
     */
    public function getDisableColumns()
    {
        return Yii::$app->request->get('readOnly', 0) ? ['actions'] : [];
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
     * @throws \yii\base\InvalidConfigException
     */
    public function getGridColumns()
    {
        return [
            'log_id' => [
                'class' => 'pvsaintpe\log\components\grid\IdColumn',
                'attribute' => 'log_id',
            ],
            'value' => [
                'class' => 'pvsaintpe\log\components\grid\DataColumn',
                'attribute' => 'value',
                'value' => function (ChangeLogSearchBase $model) {
                    if (in_array($this->attribute, $model->getBooleanAttributes())) {
                        return Yii::$app->formatter->asBoolean($model->value);
                    } elseif (array_key_exists($this->attribute, $model->getRelationAttributes())) {
                        $relation = $model->getRelationAttributes()[$this->attribute];
                        return (new Query())
                            ->from($relation['table'])
                            ->where([$relation['key'] => $model->value])
                            ->select(Configs::storageDb()->getFirstUniqueKey($relation['table']))
                            ->scalar();
                    } else {
                        return Html::tag(
                            'div',
                            $model->value,
                            [
                                'class' => 'ellipses',
                                'title' => $model->value,
                                'alt' => $model->value,
                                'style' => 'width:100px'
                            ]
                        );
                    }
                },
            ],
            'updatedBy' => [
                'class' => 'pvsaintpe\log\components\grid\DataColumn',
                'attribute' => 'updatedBy',
                'value' => function (ChangeLogSearchBase $model) {
                    if (!$model->referenceBy) {
                        return $model->updatedBy;
                    }
                    if (Yii::$app->user->can(Configs::instance()->adminPageRoute, ['id' => $model->referenceBy->id])) {
                        return Html::a(
                            $model->referenceBy->getTitleText(),
                            Url::to(['/' . Configs::instance()->adminPageRoute, 'id' => $model->referenceBy->id]),
                            ['target' => '_blank', 'data-pjax' => 0]
                        );
                    } else {
                        return $model->referenceBy->getTitleText();
                    }
                }
            ],
            'log_reason' => [
                'class' => 'pvsaintpe\log\components\grid\EditableColumn',
                'attribute' => 'log_reason',
                'value' => function (ChangeLogSearchBase $model) {
                    return $model->log_reason;
                },
                'editableOptions' => function (ChangeLogSearchBase $model, $key, $index) {
                    return [
                        'asPopover' => false,
                        'buttonsTemplate' => '{submit}',
                        'formOptions' => [
                            'action' => array_merge(
                                [Configs::instance()->pathToRoute],
                                $this->getAttributes([
                                    'attribute',
                                    'route',
                                    'hash',
                                    'table',
                                    'where'
                                ])
                            ),
                        ],
                        'editableButtonOptions' => [
                            'autoGuessInput' => true,
                        ]
                    ];
                },
                'contentOptions' => [
                    'style' => "text-overflow: ellipsis; white-space: nowrap; overflow:hidden; max-width:84px; padding-left:10px;"
                ],
                'readonly' => function ($model, $key, $index) {
                    return $model->referenceBy->id != Yii::$app->user->getId();
                },
                'refreshGrid' => false,
            ],
            'timestamp' => [
                'class' => 'pvsaintpe\log\components\grid\TimestampColumn',
            ],
            'actions' => [
                'class' => 'pvsaintpe\log\components\grid\DataColumn',
                'header' => Yii::t('payment', 'Действия'),
                'vAlign' => 'middle',
                'format' => 'raw',
                'width' => '100px',
                'value' => function (ChangeLogSearchBase $model) {
                    $buttons[] = Html::button(
                        Yii::t('models', 'Восстановить'),
                        [
                            'class' => 'btn btn-success btn-xs rollback-button',
                            'data-pjax' => 0,
                            'data-value' => $model->value,
                            'id' => $this->hash,
                        ]
                    );

                    return join(' &nbsp; ', $buttons);
                }
            ],
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return array_merge(
            [
                [['attribute', 'route', 'table', 'where', 'hash'], 'required'],
            ],
            parent::rules()
        );
    }

    /**
     * @return array
     */
    public function getSort()
    {
        return array_merge(
            parent::getSort(),
            [
                'defaultOrder' => [
                    'timestamp' => SORT_DESC
                ]
            ]
        );
    }

    /**
     * @return \pvsaintpe\search\components\ActiveDataProvider|\yii\data\DataProviderInterface
     */
    public function search()
    {
        ChangeLogSearchBase::setTableName($this->table);

        $alias = uniqid('admin');

        /** @var ActiveQuery query */
        $this->query = ChangeLogSearchBase::find();
        $this->query->join(
            'left join',
            Configs::storageDb()->getName() . '.' . Configs::instance()->adminTable . ' ' . $alias,
            $alias . '.id = ' . $this->query->a(Configs::instance()->adminColumn)
        );

        $this->query->select([
            $this->query->a('log_id'),
            $this->query->a(Configs::instance()->adminColumn) . ' AS `updatedBy`',
            $this->query->a('timestamp'),
            $this->query->a('log_reason'),
            $this->query->a($this->attribute) . ' AS `value`',
        ]);

        $this->query->andWhere(['NOT', [$this->query->a($this->attribute) => null]]);

        if ($this->where && $conditions = @unserialize($this->where)) {
            foreach ((array)$conditions as $attribute => $value) {
                $this->query->andWhere([
                    $this->query->a($attribute) => $value,
                ]);
            }
        }

        return $this->getDataProvider();
    }
}
