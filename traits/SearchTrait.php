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
        return array_merge([$this->getGridRevision()], $this->getGridToolbarButtonsBase());
    }

    /**
     * Флаг инициализации фильтров ревизиций
     * @var bool
     */
    private $initRevisionFilters;

    /**
     * @param mixed $options
     * @return ActiveDataProvider|DataProviderInterface
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function getDataProvider($options = [])
    {
        /**
         * @message Фильтры применять лишь раз (ранее вызывала ошибки)
         * @example SQLSTATE[42000]: Syntax error or access violation: 1066 Not unique table/alias: 'log'
         * @todo Разобраться почему и что вызывает задвоение join-ов.
         */
        if ($options['revisionFilters'] ?? true && !$this->initRevisionFilters) {
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
        if (!(Yii::$app->id == Configs::instance()->appId) || !Yii::$app->user->can(Configs::instance()->id)) {
            return;
        }

        if (!($this instanceof ActiveRecord) || !$this->logEnabled() || !$this->isRevisionEnabled()) {
            return;
        }

        /** @var ActiveRecord $model */
        $model = $this->getBaseModel();
        $alias = 'log';

        $logTable = $model::getLogDb()->getName() . '.' . $model::getLogTableName() . " {$alias}";
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
                $model::booleanAttributes()
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

        if ($this->getRevisionPeriod() != -1) {
            $this->query->andWhere([
                '>=',
                "{$alias}.timestamp",
                new Expression('NOW() - INTERVAL ' . $this->getRevisionPeriod() . ' DAY')
            ]);
        }

        if (count($whereConditions) > 0) {
            $whereCondition = join(' OR ', $whereConditions);
            $this->query->andWhere($whereCondition);
        } else {
            $this->query->andWhere('0=1');
        }

        $this->query->groupBy($groupBy);

        $this->initRevisionFilters = true;
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    public function getGridRevision()
    {
        if (!($this instanceof ActiveRecord)
            || !$this->logEnabled()
            || !Yii::$app->user->can(Configs::instance()->id)
            || !(Yii::$app->id == Configs::instance()->appId)
        ) {
            return [];
        }

        if (!$this->isRevisionEnabled()) {
            $options = [
                'data-pjax' => 0,
                'title' => Yii::t('changelog', 'Показать историю изменений')
            ];
            return [
                'content' => Html::tag(
                    'div',
                    join('', [
                        Html::tag(
                            'button',
                            join('', [
                                Html::tag('span', null, ['class' => 'glyphicon glyphicon-eye-open']),
                                '&nbsp;&nbsp;',
                                Html::tag('span', null, ['class' => 'fa fa-caret-down']),
                            ]),
                            [
                                'class' => 'btn btn-default dropdown-toggle',
                                'type' => 'button',
                                'data-toggle' => 'dropdown',
                                'aria-expanded' => false,
                            ]
                        ),
                        Html::tag(
                            'ul',
                            join('', [
                                Html::tag('li', Html::a(
                                    Yii::t('changelog', 'за сутки'),
                                    Url::modify(
                                        Yii::$app->getRequest()->getUrl(),
                                        ['page', 'per-page', 'revisionEnabled', 'revisionPeriod'],
                                        ['revisionEnabled' => !$this->isRevisionEnabled(), 'revisionPeriod' => 1]
                                    ),
                                    $options
                                )),
                                Html::tag('li', Html::a(
                                    Yii::t('changelog', 'за 3 дня'),
                                    Url::modify(
                                        Yii::$app->getRequest()->getUrl(),
                                        ['page', 'per-page', 'revisionEnabled', 'revisionPeriod'],
                                        ['revisionEnabled' => !$this->isRevisionEnabled(), 'revisionPeriod' => 3]
                                    ),
                                    $options
                                )),
                                Html::tag('li', Html::a(
                                    Yii::t('changelog', 'за неделю'),
                                    Url::modify(
                                        Yii::$app->getRequest()->getUrl(),
                                        ['page', 'per-page', 'revisionEnabled', 'revisionPeriod'],
                                        ['revisionEnabled' => !$this->isRevisionEnabled(), 'revisionPeriod' => 7]
                                    ),
                                    $options
                                )),
                                Html::tag('li', '', ['class' => 'divider']),
                                Html::tag('li', Html::a(
                                    Yii::t('changelog', 'за все время'),
                                    Url::modify(
                                        Yii::$app->getRequest()->getUrl(),
                                        ['page', 'per-page', 'revisionEnabled', 'revisionPeriod'],
                                        ['revisionEnabled' => !$this->isRevisionEnabled(), 'revisionPeriod' => -1]
                                    ),
                                    $options
                                )),
                            ]),
                            ['class' => 'dropdown-menu']
                        )
                    ]),
                    ['class' => 'btn-group']
                )
            ];
        } else {
            return [
                'content' => Html::a(
                    '<i class="glyphicon glyphicon-eye-close"></i>',
                    Url::modify(
                        Yii::$app->getRequest()->getUrl(),
                        ['page', 'per-page', 'revisionEnabled'],
                        ['revisionEnabled' => !$this->isRevisionEnabled()]
                    ),
                    [
                        'data-pjax' => 0,
                        'class' => 'btn btn-default btn-md',
                        'title' => Yii::t('changelog', 'Скрыть историю изменений')
                    ]
                ),
            ];
        }
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
