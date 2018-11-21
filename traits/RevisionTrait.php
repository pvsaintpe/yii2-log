<?php

namespace pvsaintpe\log\traits;

use pvsaintpe\helpers\Url;
use pvsaintpe\log\interfaces\ChangeLogInterface;
use Yii;
use pvsaintpe\log\components\ActiveRecord;
use pvsaintpe\log\components\Configs;
use yii\base\InvalidConfigException;
use pvsaintpe\helpers\Html;

/**
 * Trait RevisionTrait
 * @package pvsaintpe\log\traits
 */
trait RevisionTrait
{
    /**
     * @param $model
     * @param $attribute
     * @return bool
     */
    final public function isRevisionEnabled($model, $attribute)
    {
        /**
         * @var ActiveRecord|ChangeLogInterface $model
         */
        if ($model instanceof ChangeLogInterface
            && $model->isLogEnabled()
            && !in_array($attribute, $model->securityLogAttributes())
            && !in_array($attribute, $model->skipLogAttributes())
            && Yii::$app->user->can('changelog')
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param ActiveRecord $model
     * @param string $attribute
     * @param bool $readOnly
     * @return mixed
     * @throws InvalidConfigException
     */
    final public function renderRevisionContent(ActiveRecord $model, $attribute, $readOnly = false)
    {
        $keys = [];
        foreach (array_intersect_key($model->getAttributes(), array_flip($model::primaryKey())) as $index => $val) {
            $keys[] = $index.'='.$val;
        }
        $hash = md5($attribute . ':'.join('&', $keys));
        $where = array_intersect_key($model->getAttributes(), array_flip($model::primaryKey()));

        $afterCode = '';
        $color = Configs::instance()->revisionStyle;
        if ($model->hasAttribute($attribute) && ($cnt = $model::getLastRevisionCount($attribute, $where)) > 0) {
            $afterCode = '&nbsp;&nbsp;<sup class="red">' .  $cnt . '</sup>';
            $color = Configs::instance()->revisionActiveStyle;
        }

        /** @var Url $urlHelper */
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
                    'readOnly' => $readOnly,
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
}