<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;

/**
 * User Controller
 */
class UserController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\User';
    
    var $unauthorized_actions = [
            'login',
            'get-code',
            'send-code',
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
    
    private function sendSMS($phone, $text)
    {        
        $u = 'https://mymedhubproduct:LtTLLCmJ8Iff74mphpQNqc4BGwx@gate.smsaero.ru/v2/sms/send?number='.$phone.'&text='.$text.'&sign=SMS Aero&channel=DIRECT';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, "mymedhubproduct@gmail.com:LtTLLCmJ8Iff74mphpQNqc4BGwx");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $u);
        $u = trim(curl_exec($ch));
        curl_close($ch);
        // print_r($u);die;
    }
    
    public function actionGetCode() {
        $params = Yii::$app->request->get();
        $phone = $params['phone'];
        
        if (strlen($phone) < 11) {
            throw new \yii\web\HttpException(400, sprintf('Неверный формат телефона!'), User::ERROR_BAD_DATA);
        }
        
        $user = User::find()->where(['phone' => $phone])->one();
        
        if (empty($user)) {
            $user = new User();
            $user->username = $phone;
            $user->email = $phone.'@mm.com';
            $user->status = User::STATUS_UNACTIVATED;
            $user->created_at = time();
            $user->updated_at = time();
            $user->sms_time = time();
            $user->phone = $phone;
            $code = 1111;//random_int(1000, 9999);
            $user->code = $code;
                
            if ($user->save()) {
            //    $this->sendSMS($phone, 'Ваш код: '.$code);
                $data = new \stdClass();
                $data->success = true;
                $data->status = 200;
                return $data;
            } else {
                throw new \yii\web\HttpException(400, sprintf('Произошла ошибка базы данных! Повторите снова.'), User::ERROR_BAD_DATA);
            }
        } else {
            if (time() - $user->sms_time > 60) {
                $user->updated_at = time();
                $user->sms_time = time();
                $code = 1111;//random_int(1000, 9999);
                $user->code = $code;

                if ($user->save()) {
                //    $this->sendSMS($phone, 'Ваш код: '.$code);
                    $data = new \stdClass();
                    $data->success = true;
                    $data->status = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, sprintf('Произошла ошибка базы данных! Повторите снова.'), User::ERROR_BAD_DATA);
                }
            } else {
                throw new \yii\web\HttpException(400, sprintf('Вы сможете запросить СМС повторно через 1 минуту!'), User::ERROR_BAD_DATA);
            }
        }
    }
    
    public function actionSendCode() {
        $params = Yii::$app->request->get();
        $phone = $params['phone'];
        $code = $params['code'];
        
        $user = User::find()->where(['phone' => $phone, 'code' => $code])->one();
        
        if (!empty($user)) {
            $user->updated_at = time();
//            $user->status = User::STATUS_ACTIVE;
            $user->auth_key = md5(time());
            if ($user->save()) {
                $data = new \stdClass();
                $data->success = true;
                $data->status = 200;
                $data->profile = $user->getProfile();
                return $data;
            } else {
                throw new \yii\web\HttpException(400, sprintf('Произошла ошибка базы данных! Повторите снова.'), User::ERROR_BAD_DATA);
            }
        } else {
            throw new \yii\web\HttpException(400, sprintf('Номер телефона или код СМС неверны!'), User::ERROR_BAD_DATA);
        }
    }
    
    public function actionLogin() {
        
        $params = Yii::$app->request->get();
        
        $login = $params['login'];
        $password = $params['password'];
        
        if(!strlen($login) || !strlen($password)) {
            throw new \yii\web\HttpException(400, sprintf('Вы должны указать логин и пароль!'), User::ERROR_BAD_DATA);
        }
        
        $user = User::find()->where(['email' => $login]);
        
        if($user->exists()) {
            $user = $user->one();
            if($user->validatePassword($password)) {
                $user->auth_key = md5(time());
                $user->updated_at = time();
                if($user->save()) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    $data['profile'] = $user->getProfile();
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка! Повторите операцию снова'), User::ERROR_UNKNOWN);
                }
            } else {
                throw new \yii\web\HttpException(400, sprintf('Неправильная пара логин/пароль!'), User::ERROR_WRONG_LOGIN_PASSWORD);
            }
        } else {
            throw new \yii\web\HttpException(400, sprintf('Неправильная пара логин/пароль!'), User::ERROR_WRONG_LOGIN_PASSWORD);
        }
    }
    
    public function actionLogout() {
        
        $user = \Yii::$app->user->identity;
        $user->auth_key = null;
        
        if($user->save()) {
            $data = [];
            $data['success'] = true;
            $data['status'] = 200;
            return $data;
        } else {
            throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка! Повторите операцию снова'), User::ERROR_UNKNOWN);
        }
    }
    
    public function actionGetProfile() {
        
        $user = \Yii::$app->user->identity;
        
        $params = Yii::$app->request->get();
        
        if (array_key_exists('push_id', $params)) {
            $push_id = $params['push_id'];
            $user->push_id = $push_id;
            $user->save();
        } else {
            // $push_id = '';
        }
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['profile'] = $user->getProfile();
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
}