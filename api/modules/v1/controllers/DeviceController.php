<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\DicModels;
use common\models\Device;
use common\models\Firmware;
use common\models\DeviceData;

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

        $user = \Yii::$app->user->identity;
        
        $userAssigned = Yii::$app->authManager->getAssignments($user->id);
        $isAdmin = false;
        foreach($userAssigned as $userAssign){
            if ($userAssign->roleName == 'admin') {
                $isAdmin = true;
            }
        }

        if ($isAdmin) {
            $devices = Device::find()->all();
        } else {
            $devices = Device::find()->where(['partner_id' => $user->id])->all();
        }
        

        for ($i=0;$i<count($devices);$i++) {
            if ($devices[$i]->firmware_id != null) {
                $fw = Firmware::find()->where(['id' => $devices[$i]->firmware_id])->one();
                if (!empty($fw)) {
                    if (Yii::$app->db->schema->getTableSchema('device_data_'.$devices[$i]->id) != null) {
                        DeviceData::setDevice($devices[$i]);
                        DeviceData::setConnection(Yii::$app->db);
                        Yii::$app->db->open();
                        $dd = DeviceData::find()->one();
                        Yii::$app->db->close();
                        // print_r($dd);die;
                        if (!empty($dd)) {
                            $firmware = json_decode($fw->firmware);
                            foreach ($firmware as $key => $value) {
                                for ($y=0;$y<count($firmware[$key]->data);$y++) {
                                    $address = $firmware[$key]->data[$y]->address;
                                    // echo 'v: '.$dd->{$address};
                                    // if (property_exists($dd, (string)$address)) {
                                        $firmware[$key]->data[$y]->value = $dd->{$address};
                                    // } else {
                                        // $firmware[$key]->data[$y]->value = null;
                                    // }
                                    // print_r($firmware[$key]->data[$y]);die;
                                }
                            }
                            $devices[$i]->data = $firmware;
                        } else {
                            $devices[$i]->data = null;
                        }
                    } else {
                        $devices[$i]->data = null;
                    }
                } else {
                    $devices[$i]->data = null;
                }
            } else {
                $devices[$i]->data = null;
            }
        }
        
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
        $firmware_id = $params['firmware_id'];
        $serial = $params['serial'];
        $imei = $params['imei'];
        $address = $params['address'];
        $partner_id = $params['partner_id'];
        $date_product = $params['date_product'];
        $date_build = $params['date_build'];
        $date_shipment = $params['date_shipment'];
        $comment_admin = $params['comment_admin'];
        $connection = $params['connection'];
        $mount_city = $params['mount_city'];
        $mount_fias = $params['mount_fias'];
        $timezone = $params['timezone'];
        $status = $params['status'];


        if($id == 0) {
            $device = new Device();
            $device->name_our = $name_our;
            $device->model_id = $model_id;
            $device->firmware_id = $firmware_id;
            $device->serial = $serial;
            $device->imei = $imei;
            $device->address = $address;
            $device->partner_id = $partner_id;
            $device->date_product = $date_product;
            $device->comment_admin = $comment_admin;
            $device->date_build = $date_build;
            $device->date_shipment = $date_shipment;
            $device->connection = $connection;
            $device->mount_city = $mount_city;
            $device->mount_fias = $mount_fias;
            $device->timezone = $timezone;
            $device->status = $status;
            
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
                $device->firmware_id = $firmware_id;
                $device->serial = $serial;
                $device->imei = $imei;
                $device->address = $address;
                $device->partner_id = $partner_id;
                $device->date_product = $date_product;
                $device->comment_admin = $comment_admin;
                $device->date_build = $date_build;
                $device->date_shipment = $date_shipment;
                $device->connection = $connection;
                $device->mount_city = $mount_city;
                $device->mount_fias = $mount_fias;
                $device->timezone = $timezone;
                $device->status = $status;

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

    public function actionSavePartner() {
        
        if (!\Yii::$app->user->can('updateDevice')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $name_partner = $params['name_partner'];
        $comment_partner = $params['comment_partner'];
        $connection = $params['connection'];
        $mount_city = $params['mount_city'];
        $mount_fias = $params['mount_fias'];
        $timezone = $params['timezone'];
        $status = $params['status'];


        if($id == 0) {
            $device = new Device();
            $device->name_partner = $name_partner;
            $device->comment_partner = $comment_partner;
            $device->connection = $connection;
            $device->mount_city = $mount_city;
            $device->mount_fias = $mount_fias;
            $device->timezone = $timezone;
            $device->status = $status;
            
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
                $device->name_partner = $name_partner;
                $device->comment_partner = $comment_partner;
                $device->connection = $connection;
                $device->mount_city = $mount_city;
                $device->mount_fias = $mount_fias;
                $device->timezone = $timezone;
                $device->status = $status;

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

    public function actionAddDevicePartner() {
        
        if (!\Yii::$app->user->can('updateDevice')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }

        $user = \Yii::$app->user->identity;
        
        $userAssigned = Yii::$app->authManager->getAssignments($user->id);
        $isPartner = false;
        foreach($userAssigned as $userAssign){
            if ($userAssign->roleName == 'partner') {
                $isPartner = true;
            }
        }

        if (!$isPartner) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $serial = $params['serial'];
        $password = $params['password'];

        $device = Device::find()->where(['serial' => $serial, 'password' => $password])->one();
        if(!empty($device)) {
            $device->partner_id = $user->id;
            
            if($device->save()) {
                $data = [];
                $data['success'] = true;
                $data['status'] = 200;
                return $data;
            } else {
                throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Насос не найден!', User::ERROR_BAD_DATA);
        }
    }
    
    public function actionDeleteDevice() {
        
        if (!\Yii::$app->user->can('deleteDevice')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        if(!empty($params['id'])) {
            $id = $params['id'];
            $device = Device::find()->where(['id' => $id])->one();
            if(!empty($device)) {
                if($device->delete()) {
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

    public function actionDeleteDevicePartner() {
        
        if (!\Yii::$app->user->can('deleteDevice')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }

        $user = \Yii::$app->user->identity;
        
        $userAssigned = Yii::$app->authManager->getAssignments($user->id);
        $isPartner = false;
        foreach($userAssigned as $userAssign){
            if ($userAssign->roleName == 'partner') {
                $isPartner = true;
            }
        }

        if (!$isPartner) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        if(!empty($params['id'])) {
            $id = $params['id'];
            $device = Device::find()->where(['id' => $id, 'partner_id' => $user->id])->one();
            if(!empty($device)) {
                $device->partner_id = null;
                $device->name_partner = null;
                $device->comment_partner = null;
                if($device->save()) {
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