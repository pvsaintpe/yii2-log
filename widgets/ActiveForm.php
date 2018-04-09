<?php

namespace pvsaintpe\log\widgets;

use backend\helpers\Html;
use backend\modules\changelog\models\LogSearch;
use common\components\ActiveRecord;
use common\interfaces\LogInterface;
use kartik\form\ActiveField;
use Yii;
use yii\helpers\Url;

/**
 * Class ActiveForm
 * @package pvsaintpe\log\widgets
 */
class ActiveForm extends \kartik\form\ActiveForm
{
    /**
     * @var string
     */
    public $content;

    public $enableClientValidation = false;

    public $options = [
        'class' => 'active-form'
    ];

    public function init()
    {
        parent::init();
        echo $this->content;

        $js = <<<JS
$('form.active-form').on('submit', function(e){
        var form = $(this);
        var submit = form.find(':submit');
        form.on('beforeValidate', function () {
            submit.prop('disabled', true);
        });
        form.on('afterValidate', function () {
            submit.prop('disabled', false);
        });
        form.on('beforeSubmit', function () {
            submit.prop('disabled', true);
        });
        submit.click(function () {
            form.trigger('submit');
        });
    });
JS;
        $this->getView()->registerJs($js);

    }

    /**
     * @inheritdoc
     * @return ActiveField|\yii\widgets\ActiveField
     */
    public function field($model, $attribute, $options = [])
    {
        /** @var ActiveRecord $model */
        $field = parent::field($model, $attribute, $options);
        if ($model instanceof LogInterface && $model->logEnabled()) {
            $keys = [];
            foreach (array_intersect_key($model->getAttributes(), array_flip($model::primaryKey())) as $index => $key) {
                $keys[] = $index.'='.$key;
            }
            $hash = md5($attribute . ':'.join('&', $keys));
            $field->label(join('&nbsp;', [
                $model->getAttributeLabel($attribute),
                '<span class="change-log-area">' . Html::a(
                    Yii::t('log', 'История изменений'),
                    Url::toRoute([
                        '/changelog/default/index',
                        LogSearch::getFormName() . '[attribute]' => $attribute,
                        LogSearch::getFormName() . '[route]' => Yii::$app->urlManager->parseRequest(Yii::$app->request)[0],
                        LogSearch::getFormName() . '[hash]' => $hash,
                        LogSearch::getFormName() . '[search_class_name]' => $model->getLogClassName(),
                        LogSearch::getFormName() . '[where]' => serialize(
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
                ) .'</span>'
            ]));
        }

        return $field;
    }
}