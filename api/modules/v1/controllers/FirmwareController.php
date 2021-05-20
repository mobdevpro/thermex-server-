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

require_once 'SimpleXLSX.php';

/**
 * Firmware Controller
 */
class FirmwareController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\Firmware';
    
    var $unauthorized_actions = [
        'test',
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

    public function actionTest() {
        $params = Yii::$app->request->get();
        $id = $params['id'];

        $device = Device::find()->where(['id' => $id])->one();

        if (!empty($device)) {
            if ($device->firmware_id != null) {
                $fw = Firmware::find()->where(['id' => $device->firmware_id])->one();
                if (!empty($fw)) {
                    $fields = json_decode($fw->fields);
                    $labels = [];
                    $str = 'CREATE  TABLE IF NOT EXISTS device_data_'.$id.' (
                        `id` int(11) NOT NULL auto_increment,';
                    foreach ($fields as $key => $value) {
                        // array_push($labels, $value->label);
                        $str = $str.'`'.$value->label.'` VARCHAR(10),';
                    }
                    // print_r($labels);
                    $str = $str.'PRIMARY KEY (`id`)) ENGINE = InnoDB;';
                    echo $str;
                } else {
                    throw new \yii\web\HttpException(400, 'Прошивка не найдена!', User::ERROR_BAD_DATA);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Прошивка не найдена!', User::ERROR_BAD_DATA);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Устройство не найдено!', User::ERROR_BAD_DATA);
        }
    }
    
    public function actionGetFirmwares() {
        
        if (!\Yii::$app->user->can('getFirmwares')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $firmwares = Firmware::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['firmwares'] = $firmwares;
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

    public function actionUploadFirmware() {
        
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
            if (!file_exists('uploads/firmware/'.$id.'/')) {
                mkdir('uploads/firmware/'.$id.'/', 0777, true);
            }

            $output_file = 'uploads/firmware/'.$id.'/'.$_FILES['file']['name'];
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                $data = [];
                $data['success'] = false;
                $data['status'] = 400;
                $data['message'] = 'Файл не удалось загрузить!';
                return $data;
            }

            if ( $xlsx = SimpleXLSX::parse($output_file) ) {

                $firmware = [];
                $fields = [];
                $count = 0;
                foreach ($xlsx->rows() as $element) {
                    if ($count == 0) {
                        $count++;
                        continue;
                    }

                    if ($element[2] == '') {
                        if (!empty($obj)) {
                            array_push($firmware, $obj);
                        }
                        $obj = new \stdClass();
                        $obj->label = $element[0];
                        $obj->description = $element[1];
                        $obj->data = [];
                        // print_r($obj);
                    } else {
                        $ll = new \stdClass();
                        $ll->label = $element[0];
                        $ll->description = $element[1];
                        $ll->min = $element[2];
                        $ll->max = $element[3];
                        $ll->default = $element[4];
                        $ll->type = $element[5];
                        $ll->mode = $element[6];
                        $ll->division = $element[7];
                        $ll->address = $element[8];

                        array_push($obj->data, $ll);
                        $fields[$element[8]] = $ll;
                    }
                }

                if (count($xlsx->rows())) {
                    array_push($firmware, $obj);
                }

                // die;
                $fw->firmware = json_encode($firmware);
                $fw->fields = json_encode($fields);

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

        if($id == 0) {
            $fw = new Firmware();
            $fw->name = $name;
            $fw->firmware = json_encode($firmware);
            
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