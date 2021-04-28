<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\DicModels;

/**
 * Databases Controller
 */
class ModelsController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\DicModels';
    
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
    
    public function actionGetModels() {
        
        if (!\Yii::$app->user->can('getModels')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $models = DicModels::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['models'] = $models;
        return $data;
    
    }
    
    public function actionSave() {
        
        if (!\Yii::$app->user->can('updateModel')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $name = $params['name'];
        $image = $params['image'];

        if(!empty($name)) {
            if($id == 0) {
                $model = new DicModels();
                $model->name = $name;
                
                if($model->save()) {

                    if(!empty($image)) {
                        if (!file_exists('uploads/models/')) {
                            mkdir('uploads/models/', 0777, true);
                        }
                        $time = time();
                        $uploadfile = 'uploads/models/model-'.$model->id;
                        list($type, $data) = explode(';', $image);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($uploadfile, $data);
                        $model->image = 'model-'.$model->id;
                        $model->save();
                    }

                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                $model = DicModels::find()->where(['id' => $id])->one();
                if(!empty($model)) {
                    $model->name = $name;

                    if(!empty($image)) {
                        if (!file_exists('uploads/models/')) {
                            mkdir('uploads/models/', 0777, true);
                        }
                        $time = time();
                        $uploadfile = 'uploads/models/model-'.$model->id;
                        list($type, $data) = explode(';', $image);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($uploadfile, $data);
                        $model->image = 'model-'.$model->id;
                    }

                    if($model->save()) {
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
        } else {
            throw new \yii\web\HttpException(400, 'Заполните все поля!', User::ERROR_BAD_DATA);
        }
    }
    
    public function actionDeleteModel() {
        
        if (!\Yii::$app->user->can('deleteModel')) {
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