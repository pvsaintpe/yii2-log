<?php

namespace pvsaintpe\log\widgets;

use pvsaintpe\grid\widgets\DetailView as BaseDetailView;
use pvsaintpe\log\components\ActiveRecord;
use pvsaintpe\log\traits\RevisionTrait;

/**
 * Class DetailView
 * @package pvsaintpe\log\widgets
 */
class DetailView extends BaseDetailView
{
    use RevisionTrait;

    /**
     * @param array $options
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    protected function renderAttributeItem($options)
    {
        /** @var ActiveRecord $model */
        $model = $this->model;
        if (($attribute = $options['attribute'] ?? false) && $this->isRevisionEnabled($model, $attribute)) {
            $options['label'] .= $this->renderRevisionContent($model, $attribute, true);
        }
        return parent::renderAttributeItem($options);
    }
}