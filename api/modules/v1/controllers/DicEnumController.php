<?php
namespace api\modules\v1\controllers;

use yii;
// use shuchkin\simplexlsx;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\DicEnum;

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
        $name = $params['name'];
        $fields = $params['fields'];

        if($id == 0) {
            $enum = new DicEnum();
            $enum->name = $name;
            $enum->fields = json_encode($fields);
            
            if($enum->save()) {
                $data = [];
                $data['success'] = true;
                $data['status'] = 200;
                return $data;
            } else {
                throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
            }
        } else {
            $enum = DicEnum::find()->where(['id' => $id])->one();
            if(!empty($enum)) {
                $enum->name = $name;
                $enum->fields = json_encode($fields);

                if($enum->save()) {
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