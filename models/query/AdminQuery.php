<?php

namespace pvsaintpe\log\models\query;

use pvsaintpe\log\models\query\base\AdminQueryBase;

/**
 * Class AdminQuery
 * @package pvsaintpe\log\models\query
 */
class AdminQuery extends AdminQueryBase
{
    /**
     *
     * @return array
     */
    public function selectFilterNames()
    {
        return $this->select([
            $this->a('username name'),
            $this->a('id id')
        ])->indexBy('id')->column();
    }
    
    /**
     *
     * @return array
     */
    public function selectFilterIds()
    {
        return $this->select([
            $this->a('id name'),
            $this->a('id id')
        ])->indexBy('id')->column();
    }
}
