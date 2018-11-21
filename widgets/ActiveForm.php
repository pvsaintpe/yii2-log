<?php

namespace pvsaintpe\log\widgets;

use pvsaintpe\log\interfaces\ChangeLogInterface;
use pvsaintpe\log\traits\RevisionTrait;
use pvsaintpe\search\components\ActiveRecord;
use kartik\form\ActiveField;

/**
 * Class ActiveForm
 * @package pvsaintpe\log\widgets
 */
class ActiveForm extends \pvsaintpe\search\widgets\ActiveForm
{
    use RevisionTrait;

    /**
     * @var string
     */
    public $fieldClass = 'pvsaintpe\log\widgets\ActiveField';

    /**
     * @param \pvsaintpe\log\components\ActiveRecord $model
     * @param string $attribute
     * @param array $options
     * @return ActiveField|\pvsaintpe\log\widgets\ActiveField
     * @throws \yii\base\InvalidConfigException
     */
    public function field($model, $attribute, $options = [])
    {
        /**
         * @var ActiveRecord|ChangeLogInterface $model
         * @var \pvsaintpe\log\widgets\ActiveField $field
         */
        $field = parent::field($model, $attribute, $options);
        $label = '<span>' . $model->getAttributeLabel($attribute) . '</span>';
        if ($this->isRevisionEnabled($model, $attribute)) {
            $label = join('', [$label, $this->renderRevisionContent($model, $attribute, false)]);
        }
        $field->label($label);
        return $field;
    }
}
