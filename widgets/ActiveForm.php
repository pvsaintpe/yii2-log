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

    protected $revisionSpanStyle = 'background-color: #FF9999; padding: 5px; padding-left: 10px;';

    protected $revisionLabelStyle = 'padding-left:5px;font-weight:normal;';

    protected $revisionActiveStyle = 'color:black';
    
    protected $revisionStyle = 'color:ligthgray';

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
        if ($model instanceof ChangeLogInterface
            && $model->logEnabled()
            && !in_array($attribute, $model->securityLogAttributes())
            && Yii::$app->user->can('changelog')
        ) {
            $keys = [];
            foreach (array_intersect_key($model->getAttributes(), array_flip($model::primaryKey())) as $index => $key) {
                $keys[] = $index.'='.$key;
            }
            $hash = md5($attribute . ':'.join('&', $keys));
            $where = array_intersect_key(
                $model->getAttributes(),
                array_flip(
                    $model::primaryKey()
                )
            );
            if ($model->hasAttribute($attribute) && ($cnt = $model::getLastRevisionCount($attribute, $where)) > 0) {
                $afterCode = '<small class="label pull-right bg-red" style="margin-left:5px;">' .  $cnt . '</small>';
                $color = $this->revisionActiveStyle;
            } else {
                $afterCode = '';
                $color = $this->revisionStyle;
            }
            $label = join('', [
                '<span>' . $model->getAttributeLabel($attribute) . '</span>',
                '<span class="pull-right-container">' . $afterCode . '<span class="change-log-area pull-right" style="margin-left:5px;">',
                Html::a(
                    '<span 
                        title="' . Yii::t('log', 'История изменений') . '" 
                        alt="' . Yii::t('log', 'История изменений') . '" 
                        class="glyphicon glyphicon-eye-open" style="' . $color . '"
                    />',
                    Url::toRoute([
                        $this->pathToRoute,
                        static::getChangeLogFormName() . '[attribute]' => $attribute,
                        static::getChangeLogFormName() . '[route]' => Yii::$app->urlManager->parseRequest(
                            Yii::$app->request
                        )[0],
                        static::getChangeLogFormName() . '[hash]' => $hash,
                        static::getChangeLogFormName() . '[search_class_name]' => $model->getLogClassName(),
                        static::getChangeLogFormName() . '[where]' => serialize($where),
                    ]),
                    [
                        'class' => 'change-log-link btn-main-modal',
                        'id' => 'label-' . $hash,
                        'data-pjax' => 0,
                        'data-dismiss' => 'modal',
                        'data-id' => strtolower($this->getId() . '-' . $attribute),
                    ]
                ),
                '</span></span>'
            ]);
            $field->label($label);
            $field->setHistoryLabel($label);
        } else {
            $label = '<span>' . $model->getAttributeLabel($attribute) . '</span>';
            $field->label($label);
            $field->setHistoryLabel($label);
        }
        return $field;
    }
}
