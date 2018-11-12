<?php

namespace pvsaintpe\log\models\base;

use pvsaintpe\log\components\ActiveRecord;
use pvsaintpe\log\components\Configs;
use pvsaintpe\log\models\query\AdminQuery;
use Yii;

/**
 * This is the model class for table "admin".
 *
 * @property integer $id
 * @property string $username
 */
class AdminBase extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return Configs::instance()->adminTable;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('models', 'Логин'),
        ];
    }

    /**
     * @inheritdoc
     * @return \pvsaintpe\log\models\query\AdminQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AdminQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function singularRelations()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function pluralRelations()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function booleanAttributes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function datetimeAttributes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function modelTitle()
    {
        return Yii::t('models', 'Администратор');
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['id'];
    }

    /**
     * @inheritdoc
     */
    public static function titleKey()
    {
        return ['username'];
    }
}
