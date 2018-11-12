<?php

namespace pvsaintpe\log\models;

use pvsaintpe\log\models\base\AdminBase;

/**
 * Admin
 * @see \pvsaintpe\log\models\query\AdminQuery
 */
class Admin extends AdminBase
{
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'enabled' => 1]);
    }

    /**
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'enabled' => 1]);
    }

    /**
     * @inheritdoc
     */
    public static function titleKey()
    {
        return [
            'username'
        ];
    }
}
