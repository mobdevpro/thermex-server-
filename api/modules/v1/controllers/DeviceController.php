<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\DicModels;
use common\models\Device;

/**
 * Device Controller
 */
class DeviceController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\Device';
    
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
    
    public function actionGetDevices() {
        
        if (!\Yii::$app->user->can('getDevices')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $devices = Device::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['devices'] = $devices;
        return $data;
    
    }
    
    public function actionSave() {
        
        if (!\Yii::$app->user->can('updateDevice')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $name_our = $params['name_our'];
        $model_id = $params['model_id'];
        $serial = $params['serial'];
        $imei = $params['imei'];
        $partner_id = $params['partner_id'];
        $date_product = $params['date_product'];
        $comment_admin = $params['comment_admin'];

        if($id == 0) {
            $device = new Device();
            $device->name_our = $name_our;
            $device->model_id = $model_id;
            $device->serial = $serial;
            $device->imei = $imei;
            $device->partner_id = $partner_id;
            $device->date_product = $date_product;
            $device->comment_admin = $comment_admin;
            
            if($device->save()) {
                $data = [];
                $data['success'] = true;
                $data['status'] = 200;
                return $data;
            } else {
                throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
            }
        } else {
            $device = Device::find()->where(['id' => $id])->one();
            if(!empty($device)) {
                $device->name_our = $name_our;
                $device->model_id = $model_id;
                $device->serial = $serial;
                $device->imei = $imei;
                $device->partner_id = $partner_id;
                $device->date_product = $date_product;
                $device->comment_admin = $comment_admin;

                if($device->save()) {
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
    }
    
    public function actionDeleteDevice() {
        
        if (!\Yii::$app->user->can('deleteDevice')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        if(!empty($params['id'])) {
            $id = $params['id'];
            $model = DicModels::find()->where(['id' => $id])->one();
            if(!empty($model)) {
                if($model->delete()) {
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