<?php

namespace pvsaintpe\log\widgets;

use pvsaintpe\log\interfaces\ChangeLogInterface;
use pvsaintpe\search\components\ActiveRecord;
use pvsaintpe\log\models\ChangeLogSearch;
use pvsaintpe\search\helpers\Html;
use kartik\form\ActiveField;
use yii\helpers\Url;
use Yii;

/**
 * Class ActiveForm
 * @package pvsaintpe\log\widgets
 */
class ActiveForm extends \pvsaintpe\search\widgets\ActiveForm
{
    /**
     * @var string
     */
    protected $pathToRoute = '/changelog/default/index';

    /**
     * @var string
     */
    public $fieldClass = 'pvsaintpe\log\widgets\ActiveField';

    /**
     * @return mixed
     */
    protected function getChangeLogFormName()
    {
        return ChangeLogSearch::getFormName();
    }

    /**
     * @inheritdoc
     * @return ActiveField|\yii\widgets\ActiveField
     */
    public function field($model, $attribute, $options = [])
    {
        /**
         * @var ActiveRecord|ChangeLogInterface $model
         * @var \pvsaintpe\log\widgets\ActiveField $field
         */
        $field = parent::field($model, $attribute, $options);
        if ($model instanceof ChangeLogInterface && $model->logEnabled() && !in_array($attribute, $model->securityLogAttributes())) {
            $keys = [];
            foreach (array_intersect_key($model->getAttributes(), array_flip($model::primaryKey())) as $index => $key) {
                $keys[] = $index.'='.$key;
            }
            $hash = md5($attribute . ':'.join('&', $keys));
            $label = join('', [
                $model->getAttributeLabel($attribute),
                '&nbsp;<span class="change-log-area">',
                Html::a(
                    '<span 
                        title="' . Yii::t('log', 'История изменений') . '" 
                        alt="' . Yii::t('log', 'История изменений') . '" 
                        class="glyphicon glyphicon-eye-open" style="color:lightgray"
                    />',
                    Url::toRoute([
                        $this->pathToRoute,
                        static::getChangeLogFormName() . '[attribute]' => $attribute,
                        static::getChangeLogFormName() . '[route]' => Yii::$app->urlManager->parseRequest(Yii::$app->request)[0],
                        static::getChangeLogFormName() . '[hash]' => $hash,
                        static::getChangeLogFormName() . '[search_class_name]' => $model->getLogClassName(),
                        static::getChangeLogFormName() . '[where]' => serialize(
                            array_intersect_key(
                                $model->getAttributes(),
                                array_flip(
                                    $model::primaryKey()
                                )
                            )
                        ),
                    ]),
                    [
                        'class' => 'change-log-link btn-main-modal',
                        'id' => 'label-' . $hash,
                        'data-pjax' => 0,
                        'data-dismiss' => 'modal',
                        'data-id' => strtolower($this->getId() . '-' . $attribute),
                    ]
                ),
                '</span>'
            ]);
            $field->label($label);
            $field->setHistoryLabel($label);
        }

        return $field;
    }
}
