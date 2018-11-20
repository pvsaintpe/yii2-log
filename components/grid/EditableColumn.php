<?php

namespace pvsaintpe\log\components\grid;

use pvsaintpe\grid\components\ColumnTrait;
use pvsaintpe\helpers\Html;
use pvsaintpe\log\components\ActiveRecord;
use pvsaintpe\log\components\Configs;
use yii\base\InvalidConfigException;
use Yii;
use Closure;
use pvsaintpe\log\interfaces\ChangeLogInterface;

/**
 * Class EditableColumn
 * @package backend\components\grid
 */
class EditableColumn extends \kartik\grid\EditableColumn
{
    use ColumnTrait;

    /**
     * @var string
     */
    public $inputTemplate = 't[{index}][{attribute}]';

    /**
     * @var string
     */
    public $size = 'md';

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function renderDataCellContent($model, $key, $index)
    {
        $this->initEditableOptions($model, $key, $index);
        return join('', [
            parent::renderDataCellContent($model, $key, $index),
            $this->renderRevisionContent($model, $this->attribute)
        ]);
    }

    /**
     * @param ActiveRecord $model
     * @param mixed $key
     * @param int $index
     */
    public function initEditableOptions($model, $key, $index)
    {
        $editableOptions = $this->editableOptions;
        if (!empty($this->editableOptions) && $this->editableOptions instanceof Closure) {
            $editableOptions = call_user_func($this->editableOptions, $model, $key, $index, $this);
        }
        $this->editableOptions = array_merge(
            [
                'header' => $model->getAttributeLabel($this->attribute),
                'size' => $this->size,
                'name' => $this->attribute
            ],
            $editableOptions
        );
    }

    /**
     * @param ActiveRecord $model
     * @param string $attribute
     * @return mixed
     * @throws InvalidConfigException
     */
    public function renderRevisionContent(ActiveRecord $model, $attribute)
    {
        /**
         * @var ActiveRecord|ChangeLogInterface $model
         */
        if ($model instanceof ChangeLogInterface
            && $model->isLogEnabled()
            && !in_array($attribute, $model->securityLogAttributes())
            && Yii::$app->user->can('changelog')
        ) {
            $keys = [];
            foreach (array_intersect_key($model->getAttributes(), array_flip($model::primaryKey())) as $index => $val) {
                $keys[] = $index.'='.$val;
            }
            $hash = md5($attribute . ':'.join('&', $keys));
            $where = array_intersect_key(
                $model->getAttributes(),
                array_flip(
                    $model::primaryKey()
                )
            );
            if ($model->hasAttribute($attribute) && ($cnt = $model::getLastRevisionCount($attribute, $where)) > 0) {
                $afterCode = '&nbsp;&nbsp;<sup class="red">' .  $cnt . '</sup>';
                $color = Configs::instance()->revisionActiveStyle;
            } else {
                $afterCode = '';
                $color = Configs::instance()->revisionStyle;
            }
            $urlHelper = Configs::instance()->urlHelperClass;
            return join('', [
                '<span><span class="change-log-area" style="margin-left:5px;">',
                Html::a(
                    '<span 
                        title="' . Yii::t('log', 'История изменений') . '" 
                        alt="' . Yii::t('log', 'История изменений') . '" 
                        class="glyphicon glyphicon-eye-open" style="' . $color . '"
                    />',
                    $urlHelper::toRoute([
                        Configs::instance()->pathToRoute,
                        't[attribute]' => $attribute,
                        't[route]' => Yii::$app->urlManager->parseRequest(
                            Yii::$app->request
                        )[0],
                        't[hash]' => $hash,
                        't[search_class_name]' => $model::getLogClassName(),
                        't[where]' => serialize($where),
                        'readOnly' => 1,
                    ]),
                    [
                        'class' => 'change-log-link btn-main-modal',
                        'id' => 'label-' . $hash,
                        'data-pjax' => 0,
                        'data-dismiss' => 'modal',
                    ]
                ),
                '</span>' . $afterCode . '</span>'
            ]);
        }
        return '';
    }
}