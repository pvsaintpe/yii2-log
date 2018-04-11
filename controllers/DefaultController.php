<?php

namespace pvsaintpe\log\controllers;

use pvsaintpe\search\components\Controller;

/**
 * Class DefaultController
 * @package pvsaintpe\log\controllers
 */
class DefaultController extends Controller
{
    protected $searchClass = 'pvsaintpe\log\models\ChangeLogSearch';

    /**
     * Lists all Mail models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = $this->getSearchModel();

        return $this->renderWithAjax('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $searchModel->search(),
        ]);
    }
}