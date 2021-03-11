<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\Settings;

/**
 * Settings Controller
 */
class SettingsController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\Settings';
    
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
    
    public function actionGetSettings() {
        
        if (!\Yii::$app->user->can('getSettings')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $settings = Settings::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['settings'] = $settings;
        return $data;
    
    }
    
    public function actionSave() {
        
        if (!\Yii::$app->user->can('updateSettings')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        // $name = $params['name'];
        $description = $params['description'];
        $value = $params['value'];
        // $type = $params['type'];

        if(!empty($value)) {
            $settings = Settings::find()->where(['id' => $id])->one();
            if(!empty($settings)) {
                $settings->description = $description;
                $settings->value = $value;
                // $db->type = $type;
                
                if($settings->save()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Объект не найден!', User::ERROR_BAD_DATA);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Заполните все поля!', User::ERROR_BAD_DATA);
        }
    }
}