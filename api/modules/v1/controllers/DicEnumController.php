<?php
namespace api\modules\v1\controllers;

use yii;
// use shuchkin\simplexlsx;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\DicEnum;
use common\models\Firmware;

require_once 'SimpleXLSX.php';

/**
 * Firmware Controller
 */
class DicEnumController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\DicEnum';
    
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
    
    public function actionGetEnums() {
        
        if (!\Yii::$app->user->can('getEnums')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $enums = DicEnum::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['enums'] = $enums;
        return $data;
    
    }

    public function actionSave() {
        
        if (!\Yii::$app->user->can('updateEnum')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];

        $fw = Firmware::find()->where(['id' => $id])->one();

        if (empty($fw)) {
            throw new \yii\web\HttpException(400, 'Прошивка не найдена!', User::ERROR_UNKNOWN);
        }

        if(!empty($_FILES) && !empty($_FILES['file']) && !$_FILES['file']['error']) {
            if (!file_exists('uploads/firmware/'.$id.'/enum/')) {
                mkdir('uploads/firmware/'.$id.'/enum/', 0777, true);
            }
            $output_file = 'uploads/firmware/'.$id.'/enum/'.$_FILES['file']['name'];
            if (move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                if ( $xlsx = SimpleXLSX::parse($output_file) ) {

                    $de = DicEnum::find()->where(['firmware_id' => $id])->all();
                    for ($i=0;$i<count($de);$i++) {
                        $de[$i]->delete();
                    }

                    $count = 0;
                    $array = [];
                    $name = '';
                    foreach ($xlsx->rows() as $element) {
                        
                        if ($count == 0) {
                            $count++;
                            continue;
                        }

                        if (strlen($element[0])) {
                            if (count($array)) {
                                $enum = new DicEnum();
                                $enum->name = $name;
                                $enum->fields = json_encode($array);
                                $enum->firmware_id = $id;
                                $enum->save();
                                // print_r($array);
                                $array = [];
                                $name = $element[0];
                            } else {
                                $name = $element[0];
                            }
                        } else {
                            $obj = new \stdClass();
                            $obj->id = $element[1];
                            $obj->value = $element[2];
                            array_push($array, $obj);
                        }
                    }

                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
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
                throw new \yii\web\HttpException(400, 'Файл не загружен!', User::ERROR_UNKNOWN);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Файл не загружен!', User::ERROR_UNKNOWN);
        }
    }
    
    public function actionDeleteEnum() {
        
        if (!\Yii::$app->user->can('deleteEnum')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        if(!empty($params['id'])) {
            $id = $params['id'];
            $enum = DicEnum::find()->where(['id' => $id])->one();
            if(!empty($enum)) {
                if($enum->delete()) {
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