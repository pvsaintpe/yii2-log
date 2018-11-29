<?php

namespace pvsaintpe\log\interfaces;

use yii\db\ActiveRecordInterface as ActiveRecordInterfaceBase;

/**
 * Interface ActiveRecordInterface
 * @package pvsaintpe\log\interfaces
 */
interface ActiveRecordInterface extends ActiveRecordInterfaceBase
{
    /**
     * @return array
     */
    public function getAttributes();

    /**
     * Returns the text label for the specified attribute.
     * If the attribute looks like `relatedModel.attribute`, then the attribute will be received from the related model.
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel()
     * @see attributeLabels()
     */
    public function getAttributeLabel($attribute);

    /**
     * Returns the text hint for the specified attribute.
     * If the attribute looks like `relatedModel.attribute`, then the attribute will be received from the related model.
     * @param string $attribute the attribute name
     * @return string the attribute hint
     * @see attributeHints()
     * @since 2.0.4
     */
    public function getAttributeHint($attribute);
}