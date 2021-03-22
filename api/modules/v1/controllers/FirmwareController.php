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

    private function namedColl($Name,$firmwares){

        for($i=0,$iMax=count($firmwares); $i < $iMax; $i++){
            if ($Name == $firmwares[$i]['FirmwareName']){
    
                $response = 8+$i;
            }
        }
        return $response;
    }

    public function actionUploadFirmware() {
        
        // if(!empty($_FILES) && !$_FILES['file']['error']) {
        //     if (!file_exists('uploads/firmware/')) {
        //         mkdir('uploads/firmware/', 0777, true);
        //     }

        //     $filename = 'firmware-'.date('Y-m-d H:i:s', time());
        //     $output_file = 'uploads/firmware/'.$filename;
        //     if (move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                
        //     }
        // }

        if ( $xlsx = SimpleXLSX::parse('uploads/firmware/firmware-2021-03-22 10:44:51') ) {
            // print_r( $xlsx->rows() );
            foreach ($xlsx->rows() as $element){
                if(($element[7]!=='')){
                    $ClearRows[]=$element;
                }
            }

            // Определяем количество имеющихся прошивок
            $firmware=[];
            for($i=8,$imax=count($ClearRows[0]); $i < $imax; $i++){
                array_push($firmware,array('FirmwareName'=>$ClearRows[0][$i]));
            }
            // Определяем количество имеющихся прошивок
            // В каждую прошивку по очереди начинаем заносить данные;
            for ($i=1,$imax = count($ClearRows); $i < $imax; $i++){
                for ($f=0,$fmax=count($firmware); $f < $fmax; $f++){
                    $firmware[$f]['Data'][]=array(
                        'label'=>$ClearRows[$i][0],
                        'description'=>$ClearRows[$i][1],
                        'min'=>$ClearRows[$i][2],
                        'max'=>$ClearRows[$i][3],
                        'default'=>$ClearRows[$i][4],
                        'type'=>$ClearRows[$i][5],
                        'division'=>$ClearRows[$i][6],
                        'mode'=>$ClearRows[$i][7],
                        'address'=>$ClearRows[$i][$this->namedColl($firmware[$f]['FirmwareName'],$firmware)]
                    );
                }

            }

            for ($i=0;$i<count($firmware);$i++) {
                $fw = Firmware::find()->where(['name' => $firmware[$i]['FirmwareName']])->one();
                if (!empty($fw)) {
                    // Yii::$app->db->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                    // $command = Yii::$app->db->createCommand('DROP TABLE IF EXISTS firmware_data_'.$fw->id.';CREATE  TABLE IF NOT EXISTS firmware_data_'.$fw->id.' (
                    //     `id` int(11) NOT NULL auto_increment,
                    //     `name` VARCHAR(150) NOT NULL,
                    //     `device_id` INT(11) not NULL,
                    //     PRIMARY KEY (`id`))
                    //     ENGINE = InnoDB;');
                    // $command->execute();
                    // Yii::$app->db->pdoStatement->closeCursor();
                    // $command->closeCursor();
                    $fw->fields = json_encode($firmware[$i]['Data']);
                    $fw->save();
                } else {
                    $fw = new Firmware();
                    $fw->name = $firmware[$i]['FirmwareName'];
                    $fw->fields = json_encode($firmware[$i]['Data']);
                    $fw->save();
                }
            }

            echo'<prE>'; print_r($firmware); echo'</prE>';
        } else {
            // echo SimpleXLSX::parseError();
            $data = [];
            $data['success'] = false;
            $data['status'] = 400;
            $data['message'] = 'Файл не удалось распарсить!';
            return $data;
        }

        // $data = [];
        // $data['success'] = true;
        // $data['status'] = 200;
        // return $data;
    }
    
    public function actionSave() {
        
        if (!\Yii::$app->user->can('updateFirmware')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $name = $params['name'];
        $fields = $params['fields'];

        if($id == 0) {
            $fw = new Firmware();
            $fw->name = $name;
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
            $fw = Firmware::find()->where(['id' => $id])->one();
            if(!empty($fw)) {
                $fw->name = $name;
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