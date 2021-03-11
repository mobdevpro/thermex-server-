<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\Databases;

/**
 * Databases Controller
 */
class DatabasesController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\Databases';
    
    var $unauthorized_actions = [
            
    ];
    
    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['class'] = HttpBearerAuth::className();
        $behaviors['authenticator']['except'] = $this->unauthorized_actions;
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    public function init() {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }
    
    public function actionGetDatabases() {
        
        // if (!\Yii::$app->user->can('getDb')) {
        //     throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        // }
        
        $databases = Databases::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['databases'] = $databases;
        return $data;
    
    }
    
    public function actionSave() {
        
        // if (!\Yii::$app->user->can('updateDb')) {
        //     throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        // }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $name = $params['name'];
        $address = $params['address'];
        $db_name = $params['db_name'];
        $db_login = $params['db_login'];
        $db_password = $params['db_password'];

        if(!empty($name) && !empty($address) && !empty($db_name) && !empty($db_login) && !empty($db_password)) {
            if($id == 0) {
                $db = new Databases();
                $db->name = $name;
                $db->address = $address;
                $db->db_name = $db_name;
                $db->db_login = $db_login;
                $db->db_password = $db_password;
                
                if($db->save()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                $db = Databases::find()->where(['id' => $id])->one();
                if(!empty($db)) {
                    $db->name = $name;
                    $db->address = $address;
                    $db->db_name = $db_name;
                    $db->db_login = $db_login;
                    $db->db_password = $db_password;

                    if($db->save()) {
                        $data = [];
                        $data['success'] = true;
                        $data['status'] = 200;
                        return $data;
                    } else {
                        throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                    }
                } else {
                    throw new \yii\web\HttpException(400, 'База не найдена!', User::ERROR_BAD_DATA);
                }
            }
        } else {
            throw new \yii\web\HttpException(400, 'Заполните все поля!', User::ERROR_BAD_DATA);
        }
    }
    
    public function actionDeleteDatabase() {
        
        // if (!\Yii::$app->user->can('updateDb')) {
        //     throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        // }
        
        $params = Yii::$app->request->get();
        
        if(!empty($params['id'])) {
            $id = $params['id'];
            $db = Databases::find()->where(['id' => $id])->one();
            if(!empty($db)) {
                if($db->delete()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Объект в базе не найден!', User::ERROR_BAD_DATA);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Указаны неверные параметры!', User::ERROR_BAD_DATA);
        }
    }
}