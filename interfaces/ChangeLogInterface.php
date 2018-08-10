<?php

namespace pvsaintpe\log\interfaces;

/**
 * Interface ChangeLogInterface
 * @package pvsaintpe\log\interfaces
 */
interface ChangeLogInterface
{
    /**
     * @return bool|mixed
     */
    public function createLogTable();

    /**
     * @return bool
     */
    public function logEnabled();

    /**
     * @return string
     */
    public function getLogTableName();

    /**
     * @return string
     */
    public function getLogClassName();

    /**
     * @return array
     */
    public function securityLogAttributes();
}
