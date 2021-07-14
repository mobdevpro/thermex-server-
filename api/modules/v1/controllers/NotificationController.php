<?php
namespace api\modules\v1\controllers;

use yii;
// use shuchkin\simplexlsx;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\DicModels;
use common\models\Device;
use common\models\DeviceAlarm;
use common\models\Alarms;
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

    public function actionGetDevices() {

        $devices = Device::find()->all();
        $array = [];
        for ($i=0;$i<count($devices);$i++) {
            DeviceAlarm::setDevice($devices[$i]);
            DeviceAlarm::setConnection(Yii::$app->db);
            Yii::$app->db->open();
            $da = DeviceAlarm::find()->one();
            Yii::$app->db->close();

            if (!empty($da)) {
                $fw = Firmware::find()->where(['id' => $devices[$i]->firmware_id])->one();
                if (!empty($fw)) {
                    $alarm = json_decode($fw->alarm);
                    $alarmArray = [];
                    foreach ($da as $aa => $vv) {
                        if (strpos($aa, '_')) {
                            if ($vv == 1) {
                                for ($z=0;$z<count($alarm);$z++) {
                                    $adr = $aa;
                                    $adr = explode('_', $adr);
                                    $adr = $adr[0].'.'.$adr[1];
                                    if (str_replace(' ', '', $alarm[$z]->address) === $adr) {
                                        array_push($alarmArray, $alarm[$z]);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    if (count($alarmArray)) {
                        $devices[$i]->alarms = $alarmArray;
                        $al = Alarms::find()->where(['device_id' => $devices[$i]->id, 'is_active' => 1])->orderBy(['time' => SORT_DESC])->one();
                        $devices[$i]->time = $al->time;
                        array_push($array, $devices[$i]);
                    }
                }
            }
        }

        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['devices'] = $array;
        return $data;
    }

    public function actionGetDashboard() {
        
        // $user = \Yii::$app->user->identity;
        $array = [];
        $devices = Alarms::find()->select(['device_id'])->distinct()->all();
        for ($i=0;$i<count($devices);$i++) {
            $labels = Alarms::find()->select(['label'])->distinct()->where(['device_id' => $devices[$i]->device_id])->all();
            for ($y=0;$y<count($labels);$y++) {
                // echo $devices[$i]->device_id.' - '.$labels[$y]->label.PHP_EOL;
                $last = Alarms::find()->where(['device_id' => $devices[$i]->device_id, 'label' => $labels[$y]->label])->orderBy(['time' => SORT_DESC])->one();
                if (!empty($last)) {
                    if ($last->is_active === 1) {
                        $obj = new \stdClass();
                        $obj->label = $last->label;
                        $obj->description = $last->description;
                        $obj->is_alarm = $last->is_alarm;
                        $obj->time = $last->time;
    
                        $dev = Device::find()->where(['id' => $last->device_id])->one();
                        if (!empty($dev)) {
                            $obj->device_id = $dev->id;
                            $obj->serial = $dev->serial;
                            $obj->name_our = $dev->name_our;
                            array_push($array, $obj);
                        }
                    }
                }
            }
        }

        usort($array, function($a, $b) {
            return strtotime($a->time) < strtotime($b->time);
        });

        $last = Alarms::find()->where(['is_active' => 1])->orderBy(['time' => SORT_DESC])->limit(10)->all();        

        $array2 = [];
        for ($i=0;$i<count($last);$i++) {
            $obj = new \stdClass();
            $obj->label = $last[$i]->label;
            $obj->description = $last[$i]->description;
            $obj->is_alarm = $last[$i]->is_alarm;
            $obj->time = $last[$i]->time;

            $dev = Device::find()->where(['id' => $last[$i]->device_id])->one();
            if (!empty($dev)) {
                $obj->device_id = $dev->id;
                $obj->serial = $dev->serial;
                $obj->name_our = $dev->name_our;
                array_push($array2, $obj);
            }
        }

        usort($array2, function($a, $b) {
            return strtotime($a->time) < strtotime($b->time);
        });

        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['alarms'] = $array;
        $data['last'] = $array2;
        return $data;
    }
}