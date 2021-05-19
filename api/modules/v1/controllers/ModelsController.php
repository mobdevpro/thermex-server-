<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\DicModels;
use common\models\DicSeria;

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
        
        $seria = $params['seria'];
        $seria_id = $params['seria_id'];

        if (array_key_exists('image', $params)) {
            $image = $params['image'];
        }

        if(!empty($name)) {
            if($id == 0) {
                $model = new DicModels();
                $model->name = $name;
                $model->seria = $seria;
                $model->seria_id = $seria_id;

                if(!empty($_FILES) && !empty($_FILES['file']) && !$_FILES['file']['error']) {
                    if (!file_exists('uploads/models/docs/')) {
                        mkdir('uploads/models/docs/', 0777, true);
                    }
                    $output_file = 'uploads/models/docs/'.$_FILES['file']['name'];
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                        $model->file = $_FILES['file']['name'];
                    }
                }
                
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
                    $model->seria = $seria;
                    $model->seria_id = $seria_id;

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

                    if(!empty($_FILES) && !empty($_FILES['file']) && !$_FILES['file']['error']) {
                        if (!file_exists('uploads/models/docs/')) {
                            mkdir('uploads/models/docs/', 0777, true);
                        }
                        $output_file = 'uploads/models/docs/'.$_FILES['file']['name'];
                        if (move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                            $model->file = $_FILES['file']['name'];
                        }
                    }

                    if($model->save()) {
                        $data = [];
                        $data['success'] = true;
                        $data['status'] = 200;
                        // $data['file'] = $_FILES;
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

    public function actionGetSerias() {
        
        if (!\Yii::$app->user->can('getModels')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $serias = DicSeria::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['serias'] = $serias;
        return $data;
    
    }
    
    public function actionSaveSeria() {
        
        if (!\Yii::$app->user->can('updateModel')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $name = $params['name'];

        if(!empty($name)) {
            if($id == 0) {
                $seria = new DicSeria();
                $seria->name = $name;
                
                if($seria->save()) {

                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                $seria = DicSeria::find()->where(['id' => $id])->one();
                if(!empty($seria)) {
                    $seria->name = $name;

                    if($seria->save()) {

                        $dm = DicModels::find()->where(['seria_id' => $id])->all();
                        for ($i=0;$i<count($dm);$i++) {
                            $dm[$i]->seria = $name;
                            $dm[$i]->save();
                        }
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
    
    public function actionDeleteSeria() {
        
        if (!\Yii::$app->user->can('deleteModel')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        if(!empty($params['id'])) {
            $id = $params['id'];
            $seria = DicSeria::find()->where(['id' => $id])->one();
            if(!empty($seria)) {
                if($seria->delete()) {
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