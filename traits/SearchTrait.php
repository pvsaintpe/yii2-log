<?php

namespace pvsaintpe\log\traits;

use pvsaintpe\helpers\Url;
use pvsaintpe\log\components\ActiveRecord;
use pvsaintpe\log\components\Configs;
use pvsaintpe\search\components\ActiveDataProvider;
use pvsaintpe\search\helpers\Html;
use pvsaintpe\search\traits\SearchTrait as SearchTraitBase;
use yii\base\Exception;
use yii\data\DataProviderInterface;
use yii\base\InvalidConfigException;
use Yii;
use yii\db\Expression;

/**
 * Class SearchTrait
 * @package pvsaintpe\log\traits
 * @method DataProviderInterface search()
 */
trait SearchTrait
{
    use SearchTraitBase {
        getGridToolbarButtons as protected getGridToolbarButtonsBase;
        getDataProvider as protected getDataProviderBase;
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    protected function getGridToolbarButtons()
    {
        return array_merge(
            $this->getGridToolbarButtonsBase(),
            [
                $this->getGridRevision(),
            ]
        );
    }

    /**
     * @param mixed $options
     * @return ActiveDataProvider|DataProviderInterface
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function getDataProvider($options = [])
    {
        if ($options['revisionFilters'] ?? true) {
            $this->initRevisionFilters();
        }
        return $this->getDataProviderBase($options);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     */
    protected function initRevisionFilters()
    {
        if (!Yii::$app->user->can(Configs::instance()->id)) {
            return;
        }

        if (!($this instanceof ActiveRecord) || !$this->logEnabled() || !$this->isRevisionEnabled()) {
            return;
        }

        /** @var ActiveRecord $model */
        $model = $this->getBaseModel();
        $alias = 'log';

        $logTable = $model::getLogTableName() . " {$alias}";
        $onConditions = [];
        $groupBy = [];

        foreach ($model::primaryKey() as $key) {
            $onConditions[] = join(' = ', [$this->query->a($key), "{$alias}.{$key}"]);
            $groupBy[] = $this->query->a($key);
        }

        $whereConditions = [];
        $logColumns = $model::getLogDb()->getColumns(
            $model::getLogTableName(),
            ['log_id', 'timestamp', Configs::instance()->adminColumn]
        );

        foreach ($logColumns as $logColumn) {
            if (in_array($logColumn['Field'], $model::primaryKey())) {
                continue;
            }
            if (!in_array($logColumn['Field'], array_merge(
                $this->settingsAttributes(),
                $this->getBaseModel()::booleanAttributes()
            ))) {
                continue;
            }
            if (in_array($logColumn['Field'], $model::skipLogAttributes())) {
                continue;
            }
            if (in_array($logColumn['Field'], $model::securityLogAttributes())) {
                continue;
            }
            $whereConditions[] = "{$alias}.{$logColumn['Field']} IS NOT NULL";
        }

        $this->query->innerJoin($logTable, join(' AND ', $onConditions));
        $this->query->andWhere([
            '>=',
            "{$alias}.timestamp",
            new Expression('NOW() - INTERVAL ' . $this->getRevisionPeriod() . ' DAY')
        ]);

        if (count($whereConditions) > 0) {
            $whereCondition = join(' OR ', $whereConditions);
            $this->query->andWhere($whereCondition);
        } else {
            $this->query->andWhere('0=1');
        }

        $this->query->groupBy($groupBy);
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    public function getGridRevision()
    {
        if (!($this instanceof ActiveRecord) || !$this->logEnabled() || !Yii::$app->user->can(Configs::instance()->id)) {
            return [];
        }

        if (!$this->isRevisionEnabled()) {
            $revisionIcon = 'glyphicon-eye-open';
            $revisionLabel = Yii::t('changelog', 'Показать историю изменений за сутки');
        } else {
            $revisionIcon = 'glyphicon-eye-close';
            $revisionLabel = Yii::t('changelog', 'Скрыть историю изменений');
        }
        return [
            'content' => Html::a(
                '<i class="glyphicon ' . $revisionIcon . '"></i>',
                Url::modify(
                    Yii::$app->getRequest()->getUrl(),
                    ['page', 'per-page', 'revisionEnabled'],
                    ['revisionEnabled' => !$this->isRevisionEnabled()]
                ),
                [
                    'data-pjax' => 0,
                    'class' => 'btn btn-default btn-md',
                    'title' => $revisionLabel
                ]
            ),
        ];
    }

    /**
     * Режим показа/скрытия ревизий (0 - выключен, 1 - включен)
     * @return bool
     */
    public function isRevisionEnabled()
    {
        return (bool) Yii::$app->request->get('revisionEnabled', 0);
    }

    /**
     * Период для поиска ревизий (По умолчанию 1 день)
     * @return int
     */
    public function getRevisionPeriod()
    {
        return Yii::$app->request->get('revisionPeriod', Configs::instance()->revisionPeriod);
    }
}
