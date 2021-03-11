<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;

/**
 * Manager Controller
 */
class ManagerController extends \api\modules\v1\components\ApiController
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
    
    public function actionGetManagers() {
        
        if (!\Yii::$app->user->can('getManagers')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $connection = \Yii::$app->db;
        $command = $connection->createCommand("SELECT * FROM auth_assignment WHERE auth_assignment.item_name = 'manager'");
        $users = $command->queryAll();
        
        $managers = [];
        for ($i=0;$i<count($users);$i++) {
            $user = User::find()->where(['id' => $users[$i]['user_id']])->one();
            if (!empty($user)) {
                $obj = $user->getPublicProfile();
                array_push($managers, $obj);
            }
        }
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['managers'] = $managers;
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
        
        if (!\Yii::$app->user->can('updateManager')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $fio = $params['fio'];
        $email = $params['email'];
        $avatar = $params['avatar'];

        if($id == 0) {
            $manager = User::find()->where(['email' => $email])->one();
            if(empty($manager)) {
                $manager = new User();
                $manager->username = $email;
                $password = $this->generatePassword();
                $password = '1111';
                $manager->password_hash = md5($password);
                $manager->auth_key = md5(time());
                $manager->email = $email;
                $manager->status = User::STATUS_ACTIVE;
                $manager->created_at = time();
                $manager->updated_at = time();
                $manager->fio = $fio;
                
                if($manager->save()) {
                    if(!empty($avatar)) {
                        if (!file_exists('uploads/users/')) {
                            mkdir('uploads/users/', 0777, true);
                        }
                        $time = time();
                        $uploadfile = 'uploads/users/user-'.$manager->id;
                        list($type, $data) = explode(';', $avatar);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($uploadfile, $data);
                        $manager->avatar = 'user-'.$manager->id;
                        $manager->save();
                    }

                    $auth = Yii::$app->authManager;
                    $roleObj = $auth->getRole('manager');
                    $auth->assign($roleObj, $manager->id);

                    $this->sendMail($manager->email, "Thermex", "<b>Вы стали менеджером Thermex. Ваш пароль: </b>".$password);

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
            $manager = User::find()->where(['id' => $id])->one();
            if (!empty($manager)) {
                $profile = $manager->getPublicProfile();
                
                if (in_array('manager', $profile->roles)) {
                    $manager->fio = $fio;
                
                    if(!empty($avatar)) {
                        if (!file_exists('uploads/users/')) {
                            mkdir('uploads/users/', 0777, true);
                        }
                        $time = time();
                        $uploadfile = 'uploads/users/user-'.$manager->id;
                        list($type, $data) = explode(';', $avatar);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($uploadfile, $data);
                        $manager->avatar = 'user-'.$manager->id;
                        
                    }

                    unset($manager->roles);

                    if ($manager->save()) {
                        $data = [];
                        $data['success'] = true;
                        $data['status'] = 200;
                        return $data;
                    } else {
                        throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                    }
                } else {
                    throw new \yii\web\HttpException(400, 'Пользователь не является менеджером!', User::ERROR_BAD_DATA);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Пользователь не найден!', User::ERROR_BAD_DATA);
            }
        }
    }

    public function actionNewPassword() {
        
        if (!\Yii::$app->user->can('updateManager')) {
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

    public function actionSetManagerStatus() {
        
        if (!\Yii::$app->user->can('updateManager')) {
            throw new \yii\web\HttpException(401, 'У вас нет прав!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        $id = $params['id'];
        $active = $params['active'];
        
        $manager = User::find()->where(['id' => $id])->one();
        if (!empty($manager)) {
            $profile = $manager->getPublicProfile();
            if (in_array('manager', $profile->roles)) {
                $manager->status = $active == 1 ? User::STATUS_ACTIVE : User::STATUS_INACTIVE;
                unset($manager->roles);

                if ($manager->save()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Пользователь не является менеджером!', User::ERROR_BAD_DATA);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Пользователь не найден!', User::ERROR_BAD_DATA);
        }
    }

    public function actionDeleteManager() {
        
        if (!\Yii::$app->user->can('updateManager')) {
            throw new \yii\web\HttpException(401, 'У вас нет прав!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        $id = $params['id'];
        
        $manager = User::find()->where(['id' => $id])->one();
        if (!empty($manager)) {
            $profile = $manager->getPublicProfile();
            if (in_array('manager', $profile->roles)) {
                unset($manager->roles);
                if ($manager->delete()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Пользователь не является менеджером!', User::ERROR_BAD_DATA);
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