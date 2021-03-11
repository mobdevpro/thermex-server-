<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;

/**
 * Engineer Controller
 */
class EngineerController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\User';
    
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
    
    public function actionGetEngineers() {
        
        if (!\Yii::$app->user->can('getEngineers')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $connection = \Yii::$app->db;
        $command = $connection->createCommand("SELECT * FROM auth_assignment WHERE auth_assignment.item_name = 'engineer'");
        $users = $command->queryAll();
        
        $engineers = [];
        for ($i=0;$i<count($users);$i++) {
            $user = User::find()->where(['id' => $users[$i]['user_id']])->one();
            if (!empty($user)) {
                $obj = $user->getPublicProfile();
                array_push($engineers, $obj);
            }
        }
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['engineers'] = $engineers;
        return $data;
    
    }

    private function generatePassword() { 
        $n = 8;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 

        return $randomString; 
    }
    
    public function actionSave() {
        
        if (!\Yii::$app->user->can('updateEngineer')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $fio = $params['fio'];
        $email = $params['email'];
        $avatar = $params['avatar'];

        if($id == 0) {
            $engineer = User::find()->where(['email' => $email])->one();
            if(empty($engineer)) {
                $engineer = new User();
                $engineer->username = $email;
                $password = $this->generatePassword();
                $password = '1111';
                $engineer->password_hash = md5($password);
                $engineer->auth_key = md5(time());
                $engineer->email = $email;
                $engineer->status = User::STATUS_ACTIVE;
                $engineer->created_at = time();
                $engineer->updated_at = time();
                $engineer->fio = $fio;
                
                if($engineer->save()) {
                    if(!empty($avatar)) {
                        if (!file_exists('uploads/users/')) {
                            mkdir('uploads/users/', 0777, true);
                        }
                        $time = time();
                        $uploadfile = 'uploads/users/user-'.$engineer->id;
                        list($type, $data) = explode(';', $avatar);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($uploadfile, $data);
                        $engineer->avatar = 'user-'.$engineer->id;
                        $engineer->save();
                    }

                    $auth = Yii::$app->authManager;
                    $roleObj = $auth->getRole('engineer');
                    $auth->assign($roleObj, $engineer->id);

                    $this->sendMail($engineer->email, "Thermex", "<b>Вы стали инженером Thermex. Ваш пароль: </b>".$password);

                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Почтовый адрес уже занят!', User::ERROR_BAD_DATA);
            }
        } else {
            $engineer = User::find()->where(['id' => $id])->one();
            if (!empty($engineer)) {
                $profile = $engineer->getPublicProfile();
                
                if (in_array('engineer', $profile->roles)) {
                    $engineer->fio = $fio;
                
                    if(!empty($avatar)) {
                        if (!file_exists('uploads/users/')) {
                            mkdir('uploads/users/', 0777, true);
                        }
                        $time = time();
                        $uploadfile = 'uploads/users/user-'.$engineer->id;
                        list($type, $data) = explode(';', $avatar);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($uploadfile, $data);
                        $engineer->avatar = 'user-'.$engineer->id;
                        
                    }

                    unset($engineer->roles);

                    if ($engineer->save()) {
                        $data = [];
                        $data['success'] = true;
                        $data['status'] = 200;
                        return $data;
                    } else {
                        throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                    }
                } else {
                    throw new \yii\web\HttpException(400, 'Пользователь не является инженером!', User::ERROR_BAD_DATA);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Пользователь не найден!', User::ERROR_BAD_DATA);
            }
        }
    }

    public function actionNewPassword() {
        
        if (!\Yii::$app->user->can('updateEngineer')) {
            throw new \yii\web\HttpException(401, 'У вас нет прав!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        $id = $params['id'];
        
        $user = User::find()->where(['id' => $id])->one();
        if (!empty($user)) {
            $password = $this->generatePassword();
            $password = '1111';
            $user->password_hash = md5($password);
            if ($user->save()) {
                
                if ($this->sendMail($user->email, "Новый пароль", "<b>Ваш новый пароль: </b>".$password)) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Письмо с новым паролем не было отправлено!', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Пользователь не найден!', User::ERROR_BAD_DATA);
        }
    }

    public function actionSetEngineerStatus() {
        
        if (!\Yii::$app->user->can('updateEngineer')) {
            throw new \yii\web\HttpException(401, 'У вас нет прав!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        $id = $params['id'];
        $active = $params['active'];
        
        $engineer = User::find()->where(['id' => $id])->one();
        if (!empty($engineer)) {
            $profile = $engineer->getPublicProfile();
            if (in_array('engineer', $profile->roles)) {
                $engineer->status = $active == 1 ? User::STATUS_ACTIVE : User::STATUS_INACTIVE;
                unset($engineer->roles);

                if ($engineer->save()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Пользователь не является инженером!', User::ERROR_BAD_DATA);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Пользователь не найден!', User::ERROR_BAD_DATA);
        }
    }

    public function actionDeleteEngineer() {
        
        if (!\Yii::$app->user->can('updateEngineer')) {
            throw new \yii\web\HttpException(401, 'У вас нет прав!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        $id = $params['id'];
        
        $engineer = User::find()->where(['id' => $id])->one();
        if (!empty($engineer)) {
            $profile = $engineer->getPublicProfile();
            if (in_array('engineer', $profile->roles)) {
                unset($engineer->roles);
                if ($engineer->delete()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Пользователь не является инженером!', User::ERROR_BAD_DATA);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Пользователь не найден!', User::ERROR_BAD_DATA);
        }
    }

    private function sendMail($to, $subject, $message) {

        $header = "From:admin@thermex.ru \r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-type: text/html\r\n";

        $retval = mail($to, $subject, $message, $header);
        return $retval;
    }
}