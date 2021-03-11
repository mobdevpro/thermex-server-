<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;

/**
 * Partner Controller
 */
class PartnerController extends \api\modules\v1\components\ApiController
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
    
    public function actionGetPartners() {
        
        if (!\Yii::$app->user->can('getPartners')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $connection = \Yii::$app->db;
        $command = $connection->createCommand("SELECT * FROM auth_assignment WHERE auth_assignment.item_name = 'partner'");
        $users = $command->queryAll();
        
        $partners = [];
        for ($i=0;$i<count($users);$i++) {
            $user = User::find()->where(['id' => $users[$i]['user_id']])->one();
            if (!empty($user)) {
                $obj = $user->getPublicProfile();
                array_push($partners, $obj);
            }
        }
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['partners'] = $partners;
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
        
        if (!\Yii::$app->user->can('updatePartner')) {
            throw new \yii\web\HttpException(401, 'Операция запрещена!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $id = $params['id'];
        $fio = $params['fio'];
        $email = $params['email'];
        $avatar = $params['avatar'];

        if($id == 0) {
            $partner = User::find()->where(['email' => $email])->one();
            if(empty($partner)) {
                $partner = new User();
                $partner->username = $email;
                $password = $this->generatePassword();
                $password = '1111';
                $partner->password_hash = md5($password);
                $partner->auth_key = md5(time());
                $partner->email = $email;
                $partner->status = User::STATUS_ACTIVE;
                $partner->created_at = time();
                $partner->updated_at = time();
                $partner->fio = $fio;
                
                if($partner->save()) {
                    if(!empty($avatar)) {
                        if (!file_exists('uploads/users/')) {
                            mkdir('uploads/users/', 0777, true);
                        }
                        $time = time();
                        $uploadfile = 'uploads/users/user-'.$partner->id;
                        list($type, $data) = explode(';', $avatar);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($uploadfile, $data);
                        $partner->avatar = 'user-'.$partner->id;
                        $partner->save();
                    }

                    $auth = Yii::$app->authManager;
                    $roleObj = $auth->getRole('partner');
                    $auth->assign($roleObj, $partner->id);

                    $this->sendMail($partner->email, "Thermex", "<b>Вы стали партнером Thermex. Ваш пароль: </b>".$password);

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
            $partner = User::find()->where(['id' => $id])->one();
            if (!empty($partner)) {
                $profile = $partner->getPublicProfile();
                
                if (in_array('partner', $profile->roles)) {
                    $partner->fio = $fio;
                
                    if(!empty($avatar)) {
                        if (!file_exists('uploads/users/')) {
                            mkdir('uploads/users/', 0777, true);
                        }
                        $time = time();
                        $uploadfile = 'uploads/users/user-'.$partner->id;
                        list($type, $data) = explode(';', $avatar);
                        list(, $data)      = explode(',', $data);
                        $data = base64_decode($data);
                        file_put_contents($uploadfile, $data);
                        $partner->avatar = 'user-'.$partner->id;
                        
                    }

                    unset($partner->roles);

                    if ($partner->save()) {
                        $data = [];
                        $data['success'] = true;
                        $data['status'] = 200;
                        return $data;
                    } else {
                        throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                    }
                } else {
                    throw new \yii\web\HttpException(400, 'Пользователь не является партнером!', User::ERROR_BAD_DATA);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Пользователь не найден!', User::ERROR_BAD_DATA);
            }
        }
    }

    public function actionNewPassword() {
        
        if (!\Yii::$app->user->can('updatePartner')) {
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

    public function actionSetPartnerStatus() {
        
        if (!\Yii::$app->user->can('updatePartner')) {
            throw new \yii\web\HttpException(401, 'У вас нет прав!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        $id = $params['id'];
        $active = $params['active'];
        
        $partner = User::find()->where(['id' => $id])->one();
        if (!empty($partner)) {
            $profile = $partner->getPublicProfile();
            if (in_array('partner', $profile->roles)) {
                $partner->status = $active == 1 ? User::STATUS_ACTIVE : User::STATUS_INACTIVE;
                unset($partner->roles);

                if ($partner->save()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Пользователь не является партнером!', User::ERROR_BAD_DATA);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Пользователь не найден!', User::ERROR_BAD_DATA);
        }
    }

    public function actionDeletePartner() {
        
        if (!\Yii::$app->user->can('updatePartner')) {
            throw new \yii\web\HttpException(401, 'У вас нет прав!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->get();
        
        $id = $params['id'];
        
        $partner = User::find()->where(['id' => $id])->one();
        if (!empty($partner)) {
            $profile = $partner->getPublicProfile();
            if (in_array('partner', $profile->roles)) {
                unset($partner->roles);
                if ($partner->delete()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, 'Неизвестная ошибка! Повторите операцию снова.', User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, 'Пользователь не является партнером!', User::ERROR_BAD_DATA);
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