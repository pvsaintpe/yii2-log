<?php

namespace pvsaintpe\log\widgets;

use pvsaintpe\grid\widgets\DetailView as BaseDetailView;
use pvsaintpe\log\components\Configs;
use pvsaintpe\log\interfaces\ChangeLogInterface;
use Yii;
use pvsaintpe\helpers\Html;

/**
 * Class DetailView
 * @package pvsaintpe\log\widgets
 */
class DetailView extends BaseDetailView
{
    public $revisionEnabled = true;

    /**
     * @param array $attribute
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    protected function renderAttributeItem($attribute)
    {
        if (isset($attribute['attribute'])) {
            $attribute['label'] = $this->renderAttributeLabel($attribute['attribute'], $attribute['label']);
        }
        return parent::renderAttributeItem($attribute);
    }

    /**
     * @param string $attribute
     * @param string|null $label
     * @return string
     */
    public function renderAttributeLabel($attribute, $label = null)
    {
        $model = $this->model;
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
                $color = Configs::instance()->revisionActiveStyle;
            } else {
                $afterCode = '';
                $color = Configs::instance()->revisionStyle;
            }
            $urlHelper = Configs::instance()->urlHelperClass;
            return join('', [
                "<span>{$label}</span>",
                '<span class="pull-right-container">' . $afterCode . '<span class="change-log-area pull-right" style="margin-left:5px;">',
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
                        't[search_class_name]' => $model->getLogClassName(),
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
                '</span></span>'
            ]);
        }
        return "<span>{$label}</span>";
    }
}