<?php

namespace pvsaintpe\log\controllers;

use pvsaintpe\log\components\Configs;
use pvsaintpe\search\components\Controller;
use yii\helpers\Json;
use Yii;

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
        $request = Yii::$app->request;
        if ($request->post('hasEditable')) {
            $index = Json::decode($request->post('editableIndex'), false);
            $attribute = $request->post('editableAttribute');
            $key = $request->post('editableKey');
            $value = $request->post('t')[$index][$attribute];
            $table = $request->get('table');
            $timestamp = Configs::db()->selectScalar("SELECT `timestamp` FROM `{$table}` WHERE `log_id` = {$key}");
            Configs::db()->update($table, [$attribute => $value, 'timestamp' => $timestamp], ['log_id' => $key]);
            echo Json::encode(['output' => $value, 'message' => '']);
            exit;
        }

        $searchModel = $this->getSearchModel();

        return $this->renderWithAjax('index.php', [
            'searchModel' => $searchModel,
            'dataProvider' => $searchModel->search(),
        ]);
    }
}
