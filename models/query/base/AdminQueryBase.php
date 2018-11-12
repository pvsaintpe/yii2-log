<?php

namespace pvsaintpe\log\models\query\base;

use pvsaintpe\log\components\ActiveQuery;

/**
 * This is the ActiveQuery class for [[\common\models\Admin]].
 *
 * @see \pvsaintpe\log\models\Admin
 */
class AdminQueryBase extends ActiveQuery
{
    /**
     * @inheritdoc
     * @return \pvsaintpe\log\models\Admin[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \pvsaintpe\log\models\Admin|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @param integer|integer[] $id
     * @return $this
     */
    public function pk($id)
    {
        return $this->andWhere([$this->a('id') => $id]);
    }

    /**
     * @param integer|integer[] $id
     * @return $this
     */
    public function id($id)
    {
        return $this->andWhere([$this->a('id') => $id]);
    }

    /**
     * @param string|string[] $username
     * @return $this
     */
    public function username($username)
    {
        return $this->andWhere([$this->a('username') => $username]);
    }
}
