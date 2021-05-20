<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\DicSensor;

/**
 * Databases Controller
 */
class DicSensorController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\DicSensor';
    
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
    
    public function actionGetSensors() {
        
        if (!\Yii::$app->user->can('getFirmwares')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $sensors = DicSensor::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['sensors'] = $sensors;
        return $data;
    
    }
    
    public function actionSave() {
        
        if (!\Yii::$app->user->can('updateFirmware')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $address = $params['address'];
        
        if(!empty($address)) {
            if($id == 0) {
                $sensor = new DicSensor();
                $sensor->address = $address;

                if($sensor->save()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                $sensor = DicSensor::find()->where(['id' => $id])->one();
                if(!empty($sensor)) {
                    $sensor->address = $address;

                    if($sensor->save()) {
                        $data = [];
                        $data['success'] = true;
                        $data['status'] = 200;
                        // $data['file'] = $_FILES;
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
    
    public function actionDeleteModel() {
        
        if (!\Yii::$app->user->can('deleteFirmware')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        if(!empty($params['id'])) {
            $id = $params['id'];
            $sensor = DicSensor::find()->where(['id' => $id])->one();
            if(!empty($sensor)) {
                if($sensor->delete()) {
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