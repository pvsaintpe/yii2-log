<?php

namespace pvsaintpe\log\models;

use pvsaintpe\log\components\Configs;
use pvsaintpe\search\helpers\Html;
use pvsaintpe\search\components\ActiveQuery;
use pvsaintpe\search\components\ActiveRecord;
use pvsaintpe\search\interfaces\SearchInterface;
use Yii;

/**
 * Class ChangeLogSearch
 * @package pvsaintpe\logs\models
 */
class ChangeLogSearch extends ActiveRecord
{
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
     * @return array
     */
    public function attributes()
    {
        return [
            'attribute',
            'route',
            'hash',
            'where',
            'search_class_name'
        ];
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
            'search_class_name'
        ];
    }

    /**
     * @return string
     */
    public function formName()
    {
        return static::getFormName();
    }

    /**
     * @return string
     */
    public static function getFormName()
    {
        return 't';
    }

    /**
     * @return array
     */
    public function getLogStatusAttributes()
    {
        $searchClass = $this->search_class_name;
        /** @var ActiveRecord $logModel */
        $logModel = new $searchClass();
        return $logModel::booleanAttributes();
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
                'class' => 'pvsaintpe\log\components\grid\IdColumn',
                'attribute' => 'log_id',
                'label' => 'ID',
            ],
            'value' => [
                'class' => 'pvsaintpe\log\components\grid\DataColumn',
                'attribute' => $this->attribute,
                'label' => $this->getAttributeLabel('value')
            ],
            Configs::instance()->adminColumn => [
                'class' => 'pvsaintpe\log\components\grid\DataColumn',
                'attribute' => Configs::instance()->adminColumn,
                'allowNotSet' => true,
                'value' => function ($model) {
                    return $model->referenceBy ? $model->referenceBy->getTitleText() : null;
                }
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
                'value' => function (ActiveRecord $model) {
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
                Configs::instance()->adminColumn => Yii::t('log', 'Кем обновлено'),
            ]
        );
    }

    /**
     * @param null $params
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function search($params = null)
    {
        if (!empty($params)) {
            $this->load($params);
        }

        /** @var ActiveRecord|SearchInterface $searchClass */
        $searchClass = $this->search_class_name;

        /** @var ActiveQuery query */
        $this->query = $searchClass::find();
        $this->query->join(
            Configs::instance()->adminTable . ' admin',
             'admin.id = ' . $this->query->a(Configs::instance()->adminColumn)
        );

        $this->query->select([
            $this->query->a('log_id'),
            $this->query->a(Configs::instance()->adminColumn),
            $this->query->a('timestamp'),
            $this->query->a($this->attribute),
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
