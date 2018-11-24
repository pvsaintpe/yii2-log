<?php

namespace pvsaintpe\log\models\base;

use pvsaintpe\log\components\Configs;
use pvsaintpe\log\models\Admin;
use pvsaintpe\log\models\query\AdminQuery;
use pvsaintpe\log\traits\SearchTrait;
use pvsaintpe\search\components\ActiveQuery;
use pvsaintpe\search\components\ActiveRecord;
use Yii;

/**
 * Class ChangeLogSearchBase
 *
 * @property ActiveRecord|Admin $referenceBy
 *
 * @package pvsaintpe\log\models\base
 */
class ChangeLogSearchBase extends ActiveRecord
{
    use SearchTrait;

    /**
     * @var int
     */
    public $log_id;

    /**
     * @var mixed
     */
    public $value;

    /**
     * @var int
     */
    public $updatedBy;

    /**
     * @var string
     */
    public $timestamp;

    /**
     * @var
     */
    protected static $tableName;

    /**
     * @return \yii\db\ActiveQuery|ActiveQuery|AdminQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getReferenceBy()
    {
        return $this->hasOne(Configs::instance()->adminClass, [
            'id' => 'updatedBy'
        ]);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'log_id' => Yii::t('log', 'ID'),
                'value' => Yii::t('log', 'Значение'),
                'updatedBy' => Yii::t('log', 'Кем обновлено'),
                'timestamp' => Yii::t('log', 'Метка времени'),
            ]
        );
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'log_id',
            'value',
            'updatedBy',
            'timestamp'
        ];
    }

    /**
     * @param array $row
     * @return static
     */
    public static function instantiate($row)
    {
        return new static($row);
    }

    /**
     * @return \pvsaintpe\db\components\Connection
     */
    public static function getDb()
    {
        return Configs::db();
    }

    /**
     * @return mixed
     */
    public static function tableName()
    {
        return static::$tableName;
    }

    /**
     * @param $tableName
     */
    public static function setTableName($tableName)
    {
        static::$tableName = $tableName;
    }

    /**
     * @return array
     */
    public static function getBooleanAttributes()
    {
        return [];
    }
}
