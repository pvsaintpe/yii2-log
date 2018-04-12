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

            $field->setHistoryLabel(join('', [
                '&nbsp;<span class="change-log-area">',
                Html::a(
                    Yii::t('log', 'История изменений'),
                    Url::toRoute([
                        $this->pathToRoute,
                        $this->getChangeLogFormName() . '[attribute]' => $attribute,
                        $this->getChangeLogFormName() . '[route]' => Yii::$app->urlManager->parseRequest(Yii::$app->request)[0],
                        $this->getChangeLogFormName() . '[hash]' => $hash,
                        $this->getChangeLogFormName() . '[search_class_name]' => $model->getLogClassName(),
                        $this->getChangeLogFormName() . '[where]' => serialize(
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
            ]));
        }

        return $field;
    }
}