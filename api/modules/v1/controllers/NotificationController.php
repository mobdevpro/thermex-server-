<?php
namespace api\modules\v1\controllers;

use yii;
// use shuchkin\simplexlsx;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\DicModels;
use common\models\Device;
use common\models\Firmware;
use common\models\DicNotification;

require_once 'SimpleXLSX.php';

/**
 * Firmware Controller
 */
class NotificationController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\DicNotification';
    
    var $unauthorized_actions = [
        'upload',
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
    
    public function actionGetNotifications() {
        
        $notifications = DicNotification::find()->all();

        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['notifications'] = $notifications;
        return $data;
    
    }

    public function actionGetFirmware() {
        
        if (!\Yii::$app->user->can('getFirmwares')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        $id = $params['id'];

        $firmware = Firmware::find()->where(['id' => $id])->one();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['firmware'] = $firmware;
        return $data;
    
    }

    public function actionUpload() {
        
        // if (!\Yii::$app->user->can('updateFirmware')) {
        //     throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        // }

        // $params = Yii::$app->request->post();
        // $id = $params['id'];

        // $fw = Firmware::find()->where(['id' => $id])->one();
        // if (empty($fw)) {
        //     throw new \yii\web\HttpException(400, 'Прошивка не найдена!', User::ERROR_BAD_DATA);
        // }

        if(!empty($_FILES) && !$_FILES['file']['error']) {
            if (!file_exists('uploads/')) {
                mkdir('uploads/', 0777, true);
            }

            $output_file = 'uploads/'.$_FILES['file']['name'];
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                $data = [];
                $data['success'] = false;
                $data['status'] = 400;
                $data['message'] = 'Файл не удалось загрузить!';
                return $data;
            }

            if ( $xlsx = SimpleXLSX::parse($output_file) ) {

                // $firmware = [];
                $fields = [];
                $count = 0;
                // print_r($xlsx->rows());
                // die;
                foreach ($xlsx->rows() as $element) {
                    if ($count < 3) {
                        $count++;
                        continue;
                    }

                    $dn = DicNotification::find()->where(['label' => $element[1]])->one();
                    if (empty($dn)) {
                        $dn = new DicNotification();
                    }
                    $dn->address = str_replace(' ', '', $element[0]);
                    $dn->label = $element[1];
                    $dn->description = $element[2];
                    $dn->is_alarm = $element[3] == 1 ? 1 : 0;
                    $dn->is_button = $element[5] == 1 ? 1 : 0;
                    $obj = new \stdClass();
                    if (strlen($element[6])) {
                        $obj->compact = new \stdClass();
                        $obj->compact->header = $element[6];
                        $obj->compact->body = $element[7];
                        $obj->compact->mode = $element[8];
                        $obj->compact->season = $element[9];
                        $obj->compact->action = $element[10];
                    }
                    if (strlen($element[11])) {
                        $obj->compactl = new \stdClass();
                        $obj->compactl->header = $element[11];
                        $obj->compactl->body = $element[12];
                        $obj->compactl->mode = $element[13];
                        $obj->compactl->season = $element[14];
                        $obj->compactl->action = $element[15];
                    }
                    if (strlen($element[16])) {
                        $obj->pro = new \stdClass();
                        $obj->pro->header = $element[16];
                        $obj->pro->body = $element[17];
                        $obj->pro->mode = $element[18];
                        $obj->pro->season = $element[19];
                        $obj->pro->action = $element[20];
                    }
                    $dn->data = json_encode($obj);
                    $dn->save();
                }
                
                $data = [];
                $data['success'] = true;
                $data['status'] = 200;
                // $data['message'] = 'Файл не удалось распарсить!';
                return $data;
            } else {
                // echo SimpleXLSX::parseError();
                $data = [];
                $data['success'] = false;
                $data['status'] = 400;
                $data['message'] = 'Файл не удалось распарсить!';
                return $data;
            }
        } else {
            // echo SimpleXLSX::parseError();
            $data = [];
            $data['success'] = false;
            $data['status'] = 400;
            $data['message'] = 'Файл не удалось загрузить!';
            return $data;
        }
    }

    public function actionUploadAlarm() {
        
        if (!\Yii::$app->user->can('updateFirmware')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }

        $params = Yii::$app->request->post();
        $id = $params['id'];

        $fw = Firmware::find()->where(['id' => $id])->one();
        if (empty($fw)) {
            throw new \yii\web\HttpException(400, 'Прошивка не найдена!', User::ERROR_BAD_DATA);
        }

        if(!empty($_FILES) && !$_FILES['file']['error']) {
            if (!file_exists('uploads/firmware/'.$id.'/alarm/')) {
                mkdir('uploads/firmware/'.$id.'/alarm/', 0777, true);
            }

            $output_file = 'uploads/firmware/'.$id.'/alarm/'.$_FILES['file']['name'];
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                $data = [];
                $data['success'] = false;
                $data['status'] = 400;
                $data['message'] = 'Файл не удалось сохранить!';
                return $data;
            }

            if ( $xlsx = SimpleXLSX::parse($output_file) ) {

                $alarm = [];
                $fields = [];
                $count = 0;
                foreach ($xlsx->rows() as $element) {
                    if ($count == 0) {
                        $count++;
                        continue;
                    }

                    $obj = new \stdClass();
                    $obj->label = $element[0];
                    $obj->description = $element[1];
                    $obj->is_alarm = $element[2];
                    $obj->address = $element[4];

                    array_push($alarm, $obj);
                }

                $fw->alarm = json_encode($alarm);
                // $fw->fields = json_encode($fields);

                if ($fw->save()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    $data = [];
                    $data['success'] = false;
                    $data['status'] = 400;
                    $data['message'] = 'Файл не удалось распарсить!';
                    return $data;
                }
            } else {
                // echo SimpleXLSX::parseError();
                $data = [];
                $data['success'] = false;
                $data['status'] = 400;
                $data['message'] = 'Файл не удалось распарсить!';
                return $data;
            }
        } else {
            // echo SimpleXLSX::parseError();
            $data = [];
            $data['success'] = false;
            $data['status'] = 400;
            $data['message'] = 'Файл не удалось загрузить!';
            return $data;
        }
    }
    
    public function actionSave() {
        
        if (!\Yii::$app->user->can('updateFirmware')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $name = $params['name'];
        $firmware = $params['firmware'];

        $user = \Yii::$app->user->identity;

        if($id == 0) {
            $fw = new Firmware();
            $fw->name = $name;
            $fw->firmware = json_encode($firmware);
            $fw->author_id = $user->id;
            $fw->date = date('Y-m-d', time());
            
            if($fw->save()) {
                $data = [];
                $data['success'] = true;
                $data['status'] = 200;
                return $data;
            } else {
                throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
            }
        } else {
            $fw = Firmware::find()->where(['id' => $id])->one();
            if(!empty($fw)) {
                $fw->name = $name;
                $fw->firmware = json_encode($firmware);

                $fields = [];
                for ($i=0;$i<count($firmware);$i++) {
                    for ($y=0;$y<count($firmware[$i]['data']);$y++) {
                        $fields[$firmware[$i]['data'][$y]['address']] = $firmware[$i]['data'][$y];
                    }
                }

                $fw->fields = json_encode($fields);

                if($fw->save()) {
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
    
    public function actionDeleteFirmware() {
        
        if (!\Yii::$app->user->can('deleteFirmware')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        if(!empty($params['id'])) {
            $id = $params['id'];
            $firmware = Firmware::find()->where(['id' => $id])->one();
            if(!empty($firmware)) {
                if($firmware->delete()) {
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