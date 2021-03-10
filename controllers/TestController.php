<?php


namespace app\controllers;


use app\models\Servers;
use yii\web\Controller;
use Yii;

class TestController extends Controller
{
    public function actionIndex(){
        $servers = Servers::find()->all();
        return $this->render('index',['password'=>$servers]);
    }
}