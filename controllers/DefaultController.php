<?php

namespace pvsaintpe\log\controllers;

use pvsaintpe\search\components\Controller;

/**
 * DefaultController implements the CRUD actions for Log model.
 *
 */
class DefaultController extends Controller
{
    protected $searchClass = 'pvsaintpe\log\models\LogSearch';

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