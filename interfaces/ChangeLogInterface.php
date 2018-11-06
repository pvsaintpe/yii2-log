<?php

namespace pvsaintpe\log\interfaces;

/**
 * Interface ChangeLogInterface
 * @package pvsaintpe\log\interfaces
 */
interface ChangeLogInterface
{
    /**
     * @return bool
     */
    public function logEnabled();

    /**
     * @return array
     */
    public function skipLogAttributes();
}
