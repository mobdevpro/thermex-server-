<?php
namespace api\modules\v1\controllers;

use yii;
use api\modules\v1\components\CustomAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\Client;
use common\models\User;
use common\models\UserTasks;
use common\models\UserMonitors;
use common\models\DicMonitors;
use common\models\MonitorsToUsers;
use common\models\PatientDoctor;
use common\models\DicSpecialities;
use common\models\DicRanks;
use common\models\DicDigrees;
use common\models\DoctorDocs;
use common\models\DoctorSpecialities;

ini_set('memory_limit', '-1');
// error_reporting(E_ALL);

/**
 * User Controller
 */
class ApiController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'common\models\places';
    
    var $unauthorized_actions = [
        'get-code',
        'login',
        'register',
        'get-picture',
        'get-specs',
        'get-category',
        'get-degree',
        'gray-image',
        'link',
    ];
    
    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CustomAuth::className(),
        ];
        $behaviors['authenticator']['except'] = $this->unauthorized_actions;
        return $behaviors;
    }

    public function init() {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    public function actionLink() {
        Yii::$app->response->format = Response::FORMAT_HTML;
        // echo '<html><body><a href="tg://resolve?domain=KhadeevRV">link</a></body></html>';
        echo '<html><body><a href="gorussia://app">link</a></body></html>';
    }

    public function actionGrayImage() {

        $users = User::find()->all();
        for ($i=0;$i<count($users);$i++) {
            $userAssigned = Yii::$app->authManager->getAssignments($users[$i]->id);
            $isPacient = false;
            foreach($userAssigned as $userAssign){
                if ($userAssign->roleName == 'doctor') {
                    $isPacient = true;
                }
            }
            
            if ($isPacient) {
                $src = 'uploads/users/'.$users[$i]->avatar;
                $this->imageToGray($src);
                sleep(1);
            }
        }
    }
    
    private function sendSMS($phone, $text)
    {        
        $u = 'https://marketing:zZgLvOw13DN1TRcoi2E0wi1JZ0Ku@gate.smsaero.ru/v2/sms/send?number='.$phone.'&text='.$text.'&sign=Thermex E.&channel=DIRECT';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, "marketing@thermexenergy.ru:zZgLvOw13DN1TRcoi2E0wi1JZ0Ku");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $u);
        $u = trim(curl_exec($ch));
        curl_close($ch);
    }
    
    public function actionGetCode()
    {   
        $params = Yii::$app->request->get();
        $phone = $params['phone'];
        
        if(strlen($phone) != 11) {
            throw new \yii\web\HttpException(400, sprintf('Неверный формат номера телефона!'), User::ERROR_BAD_PHONE);
        }
        
        $user = User::find()->where(['phone' => $phone])->one();
        if(!empty($user)) {
            if(time() - $user->sms_time < 2*60) {
                throw new \yii\web\HttpException(400, sprintf('Следующую СМС с кодом можно запросить через 2 минуты!'), User::ERROR_SMS_OFTEN);
            } else {
                
                if ($user->status == User::STATUS_DELETED) {
                    throw new \yii\web\HttpException(401, sprintf('Пользователь заблокирован!'), User::ERROR_ACCESS_DENIED);
                }
//                $user = new Client();
                $code = 1111;//random_int(1000, 9999);
                $user->code = $code;
                $user->sms_time = time();
                if($user->save()) {
//                    $this->sendSMS($phone, 'Ваш код: '.$code);
                    $data = new \stdClass();
                    $data->success = true;
                    $data->status = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
                }
            }
        } else {
            
            $user = new User();
            $user->username = $phone;
            $user->email = $phone.'@mmh.com';
            $user->status = User::STATUS_UNACTIVATED;
            $user->created_at = time();
            $user->updated_at = time();
            $user->sms_time = time();
            $user->phone = $phone;
            $code = 1111;//random_int(1000, 9999);
            $user->code = $code;
                
            if ($user->save()) {
//                $this->sendSMS($phone, 'Ваш код: '.$code);
                $data = new \stdClass();
                $data->success = true;
                $data->status = 200;
                return $data;
            } else {
                throw new \yii\web\HttpException(400, sprintf('Произошла ошибка базы данных! Повторите снова.'), User::ERROR_BAD_DATA);
            }
        }
    }
    
    public function actionLogin() {
        
        echo 'aaaaaaa';die;
        $params = Yii::$app->request->get();
        $phone = $params['phone'];
        $code = $params['code'];
        $push_id = $params['push_id'];
        
        if(strlen($phone) != 11) {
            throw new \yii\web\HttpException(400, sprintf('Неверный формат номера телефона!'), Client::ERROR_BAD_PHONE);
        }
        
        $user = User::find()->where(['phone' => $phone, 'code' => $code])->one();
        
        if(!empty($user)) {
            $user->updated_at = time();
            $user->auth_key = md5(time());
            $user->push_id = $push_id;
            
//            if ($user->status == User::STATUS_UNACTIVATED) {
//                $auth = Yii::$app->authManager;
//                $roleObj = $auth->getRole('pacient');
//                $auth->assign($roleObj, $user->id);
//                $user->status = User::STATUS_ACTIVE;
//            }
            
            if($user->save()) {
                $data = new \stdClass();
                $data->success = true;
                $data->status = 200;
                $data->user = $user->getProfile();
                return $data;
            } else {
                throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
            }
        } else {
            throw new \yii\web\HttpException(400, sprintf('Номер телефона или код СМС не верны!'), Client::ERROR_PHONE_OR_CODE_WRONG);
        }
    }
    
    public function actionRegisterPatient() {
        
        $params = Yii::$app->request->post();
        
        $role = $params['role'];
        $fio = $params['fio'];
        $sex = $params['sex'];
        $birthday = $params['birthday'];
        $height = $params['height'];
        $weight = $params['weight'];
        $city = $params['city'];
        $email = $params['email'];
         
        if ($role == 'pacient') {
            
            $user = \Yii::$app->user->identity;
            $roles = [];
            $userAssigned = Yii::$app->authManager->getAssignments($user->id);
            foreach($userAssigned as $userAssign){
                array_push($roles, $userAssign->roleName);
            }
            
            if (count($roles)) {
//                throw new \yii\web\HttpException(400, 'У Вас уже есть роль!', User::ERROR_UNKNOWN);
            } else {
                $auth = Yii::$app->authManager;
                $role = $auth->getRole($role);
                $auth->assign($role, $user->id);
            }
            
            $user->fio = $fio;
            $user->email2 = $email;
            $user->birthday = $birthday;
            $user->address = $city;
            $user->sex = $sex;
            $user->height = $height;
            $user->weight = $weight;

            if ($user->status == User::STATUS_UNACTIVATED) {
                $user->status = User::STATUS_ACTIVE;
            }

            if(!empty($_FILES) && !empty($_FILES['file']) && !$_FILES['file']['error']) {
                if (!file_exists('uploads/users/')) {
                    mkdir('uploads/users/', 0777, true);
                }
                $output_file = 'uploads/users/avatar-'.$user->id;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                    $user->avatar = 'avatar-'.$user->id;
                } else {

                }
                $this->imageToGray($output_file);
            }
            
            if($user->save()) {
                $data = new \stdClass();
                $data->success = true;
                $data->status = 200;
                $data->profile = $user->getProfile();
                return $data;
            } else {
                throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
            }
            
        } else {
            throw new \yii\web\HttpException(401, 'Ошибка регистрации! Повторите снова.', User::ERROR_ACCESS_DENIED);
        }
    }

    private function imageToGray($src) {
        if (file_exists($src)) {
            try {
                if (exif_imagetype($src) == IMAGETYPE_GIF) {
                    $im = imagecreatefromgif($src);
                }
                else if (exif_imagetype($src) == IMAGETYPE_JPEG) {
                    $im = imagecreatefromjpeg($src);
                }
                else if (exif_imagetype($src) == IMAGETYPE_PNG) {
                    $im = imagecreatefrompng($src);
                }
                if (!empty($im)) {
                    if($im && imagefilter($im, IMG_FILTER_GRAYSCALE)) {
                        imagepng($im, $src);
                    }
                    imagedestroy($im);
                }
            } catch (Exception $e) {
                
            }
        } else {
            echo 'not exist'.PHP_EOL;
        }
    }
    
    public function actionRegisterDoctor() {
        
        $params = Yii::$app->request->post();
        
        $role = $params['role'];
        $fio = $params['fio'];
        $sex = $params['sex'];
        $email = $params['email'];
        $city = $params['city'];
        $birthday = $params['birthday'];
        $specs = $params['specs'];
        $workplace = $params['workplace'];
        $stage = $params['stage'];
        $degree = $params['degree'];
        $rank = $params['rank'];
        $education = $params['education'];
        $dop_education = $params['dop_education'];
        $special_text = $params['special_text'];
         
        if ($role == 'doctor') {
            
            $user = \Yii::$app->user->identity;
            $roles = [];
            $userAssigned = Yii::$app->authManager->getAssignments($user->id);
            foreach($userAssigned as $userAssign){
                array_push($roles, $userAssign->roleName);
            }
            
            if (count($roles)) {
//                throw new \yii\web\HttpException(400, 'У Вас уже есть роль!', User::ERROR_UNKNOWN);
            } else {
                $auth = Yii::$app->authManager;
                $role = $auth->getRole($role);
                $auth->assign($role, $user->id);
            }
            
            $user->fio = $fio;
            $user->email2 = $email;
            $user->birthday = $birthday;
            $user->address = $city;
            $user->workplace = $workplace;
            $user->sex = $sex;
            $user->stage = $stage;
            $user->education = $education;
            $user->dop_education = $dop_education;
            $user->special_text = $special_text;

            $sp = DicSpecialities::find()->where(['id' => $specs])->one();
        
            if (!empty($sp)) {
                $ds = DoctorSpecialities::find()->where(['doctor_id' => $user->id, 'speciality_id' => $sp->id])->one();
                if (empty($ds)) {
                    $dd = new \common\models\DoctorSpecialities();
                    $dd->doctor_id = $user->id;
                    $dd->speciality_id = $sp->id;
                    $dd->save();
                }
            }
            
            $sp = DicDigrees::find()->where(['id' => $degree])->one();
            
            if (!empty($sp)) {
                $user->degree = $sp->name;
                $user->degree_id = $sp->id;
            }
            
            $sp = DicRanks::find()->where(['id' => $rank])->one();
            
            if (!empty($sp)) {
                $user->rank = $sp->name;
                $user->rank_id = $sp->id;
            }
                
            if ($user->status == User::STATUS_UNACTIVATED) {
                $user->status = User::STATUS_NEW_DOCTOR;
            }

            if(!empty($_FILES) && !empty($_FILES['file']) && !$_FILES['file']['error']) {
                if (!file_exists('uploads/users/')) {
                    mkdir('uploads/users/', 0777, true);
                }
                $output_file = 'uploads/users/avatar-'.$user->id;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                    $user->avatar = 'avatar-'.$user->id;
                } else {

                }
                $this->imageToGray($output_file);
            }
            
            if (!empty($_FILES)) {
                foreach ($_FILES as $key => $value) {
                    if (strpos($key, 'docs_') != -1) {
                        if (!$value['error']) {
                            if (!file_exists('uploads/docs/')) {
                                mkdir('uploads/docs/', 0777, true);
                            }
                            $time = time();
                            sleep(1);
                            $dd = new DoctorDocs();
                            $dd->doctor_id = $user->id;
                            $dd->created_time = date('Y-m-d h:i:s', $time);
                            $output_file = 'uploads/docs/doc-'.$user->id.'-'.$time.'.png';
                            if (move_uploaded_file($value['tmp_name'], $output_file)) {
                                $dd->doc = 'doc-'.$user->id.'-'.$time.'.png';
                                $dd->save();
                            }
                        }
                    }
                }
            }
            
            if($user->save()) {
                $data = new \stdClass();
                $data->success = true;
                $data->status = 200;
                $data->profile = $user->getProfile();
//                $data->files = $_FILES;
                return $data;
            } else {
                throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
            }
            
        } else {
            throw new \yii\web\HttpException(401, 'Ошибка регистрации! Повторите снова.', User::ERROR_ACCESS_DENIED);
        }
    }
    
    public function actionSavePatient() {
        
        $post = Yii::$app->request->post();
        $user = \Yii::$app->user->identity;
        
        $fio = $post['fio'];
        $sex = $post['sex'];
        $height = $post['height'];
        $place = $post['place'];
        $birthday = $post['birthday'];
         
        $user = \Yii::$app->user->identity;
        $user->fio = $fio;
//        $user->email2 = $email;
        $user->birthday = $birthday;
        $user->address = $place;
        $user->sex = $sex;
        $user->height = $height;
        
        if(!empty($_FILES) && !empty($_FILES['file']) && !$_FILES['file']['error']) {
            if (!file_exists('uploads/users/')) {
                mkdir('uploads/users/', 0777, true);
            }
            $output_file = 'uploads/users/avatar-'.$user->id;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                $user->avatar = 'avatar-'.$user->id;
            }
            $this->imageToGray($output_file);
        }
        
        if($user->save()) {
            $data = new \stdClass();
            $data->success = true;
            $data->status = 200;
            $data->profile = $user->getProfile();
            return $data;
        } else {
            throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
        }
    }
    
    public function actionSaveDoctor() {
        
        $post = Yii::$app->request->post();
        $user = \Yii::$app->user->identity;
        
        $fio = $post['fio'];
        $stage = $post['stage'];
        $price = $post['price'];
         
        $user = \Yii::$app->user->identity;
        $user->fio = $fio;
        $user->stage = $stage;
        $user->price = $price;
        
        if(!empty($_FILES) && !empty($_FILES['file']) && !$_FILES['file']['error']) {
            if (!file_exists('uploads/users/')) {
                mkdir('uploads/users/', 0777, true);
            }
            $output_file = 'uploads/users/avatar-'.$user->id;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $output_file)) {
                $user->avatar = 'avatar-'.$user->id;
            } else {

            }
            $this->imageToGray($output_file);
        } else {
            
        }
        
        if($user->save()) {
            $data = new \stdClass();
            $data->success = true;
            $data->status = 200;
            $data->profile = $user->getProfile();
            return $data;
        } else {
            throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
        }
    }
    
    public function actionGetProfile() {
        
        $user = \Yii::$app->user->identity;
        
        $post = Yii::$app->request->get();
        $push_id = $post['push_id'];

        $user->push_id = $push_id;
        $user->access_time = time();
        $user->save();
        
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        $data->user = $user->getProfile();
        return $data;
    }
    
    public function actionLogout() {
        
        $user = \Yii::$app->client->identity;
        
        $user->access_token = NULL;
        $user->access_time = time();
        $user->save();
        
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        return $data;
    }
    
    public function actionGetDoctors() {
        
        $connection = \Yii::$app->db;
        $patient = \Yii::$app->user->identity;
        
        $command = $connection->createCommand("SELECT * FROM auth_assignment WHERE auth_assignment.item_name = 'doctor'");
        $users = $command->queryAll();
        
        $doctors = [];
        for ($i=0;$i<count($users);$i++) {
            $user = User::find()->where(['id' => $users[$i]['user_id'], 'status' => User::STATUS_ACTIVE])->one();
            if (!empty($user)) {
                $obj = $user->getPublicProfile();
                $note = PatientDoctor::find()->where(['patient_id' => $patient->id, 'doctor_id' =>$user->id])->orderBy(['id' => SORT_DESC])->one();
                if (!empty($note)) {
                    $obj->myDoctor = $note->status;
                } else {
                    $obj->myDoctor = 'none';
                }
                array_push($doctors, $obj);
            }
        }
        
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        $data->doctors = $doctors;
        return $data;
    }
    
    public function actionGetDoctor() {
        
        $patient = \Yii::$app->user->identity;
        $params = Yii::$app->request->get();
        $doctor_id = $params['id'];
        
        
        $user = User::find()->where(['id' => $doctor_id])->one();
        if (!empty($user)) {
            $obj = $user->getPublicProfile();
            $note = PatientDoctor::find()->where(['patient_id' => $patient->id, 'doctor_id' =>$user->id])->orderBy(['id' => SORT_DESC])->one();
            if (!empty($note)) {
                $obj->myDoctor = $note->status;
            } else {
                $obj->myDoctor = 'none';
            }
            $data = new \stdClass();
            $data->success = true;
            $data->status = 200;
            $data->doctor = $obj;
            return $data;
        } else {
            throw new \yii\web\HttpException(400, sprintf('Доктор не найден!'), User::ERROR_BAD_DATA);
        }
    }
    
    public function actionSetWebrtcData() {
        
        $user = \Yii::$app->user->identity;
        
        $post = Yii::$app->request->post();
        $login = $post['login'];
        $password = $post['password'];
        $userId = $post['userId'];
        
        $user->webrtc_login = $login;
        $user->webrtc_password = $password;
        $user->webrtc_userId = $userId;
        
        if ($user->save()) {
            $data = new \stdClass();
            $data->success = true;
            $data->status = 200;
            $data->user = $user->getProfile();
            return $data;
        } else {
            throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
        }
    }
    
    public function actionGetMonitors() {
        
        $user = \Yii::$app->user->identity;
        
        $mymonitors = MonitorsToUsers::find()->where(['user_id' => $user->id])->all();
        
        if (empty($mymonitors)) {
            $monitors = DicMonitors::find()->where(['is_default' => 1])->all();
        } else {
            $monitors = [];
            for ($i=0;$i<count($mymonitors);$i++) {
                $dm = DicMonitors::find()->where(['id' => $mymonitors[$i]['monitor_id']])->one();
                if ($mymonitors[$i]['source']) {

                    if ($mymonitors[$i]['monitor_id'] == 19) {
                        $um = UserMonitors::find()->where(['user_id' => $user->id, 'monitor_id' => 18])->orderBy(['date' => SORT_DESC])->all();
                    } else {
                        $um = UserMonitors::find()->where(['user_id' => $user->id, 'monitor_id' => $mymonitors[$i]['monitor_id']])->orderBy(['date' => SORT_DESC])->all();
                    }
                    $uu = \common\models\DicUnits::find()->where(['id' => $dm->unit_id])->one();
                    if (!empty($uu)) {
                        if ($uu->name != 'Нет') {
                            $dm->unit_name = $uu->name;
                        }
                    }
                    if (!empty($um)) {
                        $dm->monitors = $um;
                    }
                }
                $dm->source = $mymonitors[$i]['source'];
                array_push($monitors, $dm);
            }
        }
        
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        $data->monitors = $monitors;
        return $data;
    }
    
    public function actionGetPacientMonitors() {
        
        $doctor = \Yii::$app->user->identity;
        $params = Yii::$app->request->get();
        $pacient_id = $params['pacient_id'];
        
        $pd = PatientDoctor::find()->where(['doctor_id' => $doctor->id, 'patient_id' => $pacient_id])->one();
        
        if (empty($pd)) {
            throw new \yii\web\HttpException(400, sprintf('Вы не являетесь лечащим врачом этого пациента!'), Client::ERROR_UNKNOWN);
        }
        $user = User::find()->where(['id' => $pacient_id])->one();
        if (empty($user)) {
            throw new \yii\web\HttpException(400, sprintf('Пациент не найден!'), Client::ERROR_UNKNOWN);
        }
        
        $mymonitors = MonitorsToUsers::find()->where(['user_id' => $user->id])->all();
        
        if (empty($mymonitors)) {
            $monitors = DicMonitors::find()->where(['is_default' => 1])->all();
        } else {
            $monitors = [];
            for ($i=0;$i<count($mymonitors);$i++) {
                $dm = DicMonitors::find()->where(['id' => $mymonitors[$i]['monitor_id']])->one();
                if ($mymonitors[$i]['source']) {
                    
                    if ($mymonitors[$i]['monitor_id'] == 19) {
                        $um = UserMonitors::find()->where(['user_id' => $user->id, 'monitor_id' => 18])->orderBy(['date' => SORT_DESC])->all();
                    } else {
                        $um = UserMonitors::find()->where(['user_id' => $user->id, 'monitor_id' => $mymonitors[$i]['monitor_id']])->orderBy(['date' => SORT_DESC])->all();
                    }
                    $uu = \common\models\DicUnits::find()->where(['id' => $dm->unit_id])->one();
                    if (!empty($uu)) {
                        if ($uu->name != 'Нет') {
                            $dm->unit_name = $uu->name;
                        }
                    }
                    if (!empty($um)) {
                        $this->monitorStatus($um);
                        $dm->monitors = $um;
                    }
                }
//                if (!empty($mymonitors[$i]['source'])) {
                    $dm->source = $mymonitors[$i]['source'];
//                }
                
                array_push($monitors, $dm);
            }
        }
//        die;
        
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        $data->monitors = $monitors;
        return $data;
    }
    
    private function monitorStatus($um) {
        
    }
    
    public function actionGetPacientMonitorById() {
        
        $doctor = \Yii::$app->user->identity;
        $params = Yii::$app->request->get();
        $pacient_id = $params['pacient_id'];
        $monitor_id = $params['monitor_id'];
        
        $pd = PatientDoctor::find()->where(['doctor_id' => $doctor->id, 'patient_id' => $pacient_id])->one();
        
        if (empty($pd)) {
            throw new \yii\web\HttpException(400, sprintf('Вы не являетесь лечащим врачом этого пациента!'), User::ERROR_ACCESS_DENIED);
        }
        $user = User::find()->where(['id' => $pacient_id])->one();
        if (empty($user)) {
            throw new \yii\web\HttpException(400, sprintf('Пациент не найден!'), User::ERROR_UNKNOWN);
        }
        
        $mymonitor = MonitorsToUsers::find()->where(['user_id' => $user->id, 'monitor_id' => $monitor_id])->one();
        
        $dm = DicMonitors::find()->where(['id' => $monitor_id])->one();
        
        if (empty($dm)) {
            throw new \yii\web\HttpException(400, sprintf('Монитор не найден!'), User::ERROR_BAD_DATA);
        }
        
        if (!empty($mymonitor)) {
            if ($mymonitor->source && $dm->enter_type == 'auto') {
                $um = UserMonitors::find()->where(['user_id' => $user->id, 'monitor_id' => $monitor_id])->orderBy(['date' => SORT_DESC])->all();
                if (!empty($um)) {
                    $dm->monitors = $um;
                }
            } else if ($dm->enter_type == 'manual') {
                $um = UserMonitors::find()->where(['user_id' => $user->id, 'monitor_id' => $monitor_id])->orderBy(['date' => SORT_DESC])->all();
                if (!empty($um)) {
                    $dm->monitors = $um;
                }
            }
            $dm->source = $mymonitor->source;
        }     
        
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        $data->monitor = $dm;
        return $data;
    }
    
    public function actionGetAllMonitors() {
        
        $user = \Yii::$app->user->identity;
        
        $monitors = DicMonitors::find()->all();
        $mymonitors = MonitorsToUsers::find()->where(['user_id' => $user->id])->all();
        
        for ($i = 0; $i<count($monitors);$i++) {
            for ($y = 0;$y<count($mymonitors);$y++) {
                if ($monitors[$i]->id == $mymonitors[$y]->monitor_id) {
                    $monitors[$i]->my = true;
                    break;
                }
            }
        }
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        $data->monitors = $monitors;
        return $data;
    }
    
    public function actionGetAllMonitorsForPacient() {
        
        $user = \Yii::$app->user->identity;
        $params = Yii::$app->request->get();
        $pacient_id = $params['pacient_id'];
        
        $pd = PatientDoctor::find()->where(['patient_id' => $pacient_id, 'doctor_id' => $user->id])->one();
        
        if (empty($pd)) {
            throw new \yii\web\HttpException(400, 'Вы не являетесь лечащим врачом данного пациента!', User::ERROR_ACCESS_DENIED);
        }
        
        $monitors = DicMonitors::find()->all();
        $mymonitors = MonitorsToUsers::find()->where(['user_id' => $pacient_id])->all();
        
        for ($i = 0; $i<count($monitors);$i++) {
            for ($y = 0;$y<count($mymonitors);$y++) {
                if ($monitors[$i]->id == $mymonitors[$y]->monitor_id) {
                    $monitors[$i]->my = true;
                    break;
                }
            }
        }
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        $data->monitors = $monitors;
        return $data;
    }

    private function updateTasks($user) {
        $mtu = MonitorsToUsers::find()->where(['user_id' => $user->id])->all();
        
        for($i=0;$i<count($mtu);$i++) {
            
            $dm = DicMonitors::find()->where(['id' => $mtu[$i]->monitor_id])->one();    
            if (empty($dm)) {
                continue;
            }

            $norm1 = $dm->norm1;
            $norm2 = $dm->norm2;

            $um = UserMonitors::find()->where(['user_id' => $mtu[$i]->user_id, 'monitor_id' => $mtu[$i]->monitor_id, 'date' => date('Y-m-d 00:00:00', time())])->one();
            if (empty($um)) {
                $um2 = UserMonitors::find()->where(['user_id' => $mtu[$i]->user_id, 'monitor_id' => $mtu[$i]->monitor_id])->orderBy(['id' => SORT_DESC])->one();
                if (!empty($um2)) {
                    if ($um2->norm1 != null) {
                        $norm1 = $um2->norm1;
                        $norm2 = $um2->norm2;
                    }
                }

                $um = new UserMonitors();
                $um->user_id = $mtu[$i]->user_id;
                $um->monitor_id = $mtu[$i]->monitor_id;
                $um->date = date('Y-m-d 00:00:00', time());
                $um->norm1 = $norm1;
                $um->norm2 = $norm2;
                $um->save();
            }

            if ($dm->isTask == 1) {
                $ut = UserTasks::find()->where(['type' => 'monitor', 'pacient_id' => $mtu[$i]->user_id, 'task_time' => date('Y-m-d 23:59:59', time()), 'monitor_id' => $mtu[$i]->monitor_id])->one();
                // $ut = UserTasks::find()->where(['type' => 'monitor', 'pacient_id' => 2, 'task_time' => date('Y-m-d 23:59:59', time()), 'monitor_id' => $mtu[$i]->monitor_id])->one();
                
                if (empty($ut)) {
                    $ut = new UserTasks();
                    $ut->pacient_id = $mtu[$i]->user_id;
                    $ut->type = 'monitor';
                    $ut->monitor_id = $mtu[$i]->monitor_id;
                    $ut->task_time = date('Y-m-d 23:59:59', time());
                    $ut->name = $dm->name;
                    $ut->icon = $dm->icon;
                    $ut->description = $dm->description;
                    $ut->save();
                }
            }
        }
    }
    
    public function actionUpdateMyMonitors() {
        
        $user = \Yii::$app->user->identity;
        $params = Yii::$app->request->post();
        
        $monitors = $params['monitors'];
        $task = $params['task'];
        

        if ($task) {
            $mm = MonitorsToUsers::find()->where(['user_id' => $user->id])->all();
        
            for ($i=0;$i<count($mm);$i++) {
                $dm = DicMonitors::find()->where(['id' => $mm[$i]->monitor_id, 'isTask' => 1])->one();
                if (!empty($dm)) {
                    if (!in_array($mm[$i]->monitor_id, $monitors)) {
                        $mm[$i]->delete();
                        $ut = UserTasks::find()->where(['monitor_id' => $mm[$i]->monitor_id, 'type' => 'monitor', 'pacient_id' => $user->id, 'task_time' => date('Y-m-d 23:59:59', time())])->one();
                        if (!empty($ut)) {
                            $ut->delete();
                        }
                    }
                }
            }
        } else {
            $mm = MonitorsToUsers::find()->where(['user_id' => $user->id])->all();
        
            for ($i=0;$i<count($mm);$i++) {
                if (!in_array($mm[$i]->monitor_id, $monitors)) {
                    $mm[$i]->delete();
                    $ut = UserTasks::find()->where(['monitor_id' => $mm[$i]->monitor_id, 'type' => 'monitor', 'pacient_id' => $user->id, 'task_time' => date('Y-m-d 23:59:59', time())])->one();
                    if (!empty($ut)) {
                        $ut->delete();
                    }
                }
            }
        }
        
        for ($i=0;$i<count($monitors);$i++) {
            
            $dm = DicMonitors::find()->where(['id' => $monitors[$i]])->one();
            if (!empty($dm)) {
                
                $mtu = MonitorsToUsers::find()->where(['user_id' => $user->id, 'monitor_id' => $monitors[$i]])->one();
                if (empty($mtu)) {
                    $mtu = new MonitorsToUsers();
                    $mtu->monitor_id = $monitors[$i];
                    $mtu->user_id = $user->id;
                    if ($dm->enter_type == 'manual') {
                        $mtu->source = 'manual';
                    }
                    $mtu->save();
                }
                if ($dm->isTask == 1) {
                    $ut = UserTasks::find()->where(['monitor_id' => $monitors[$i], 'type' => 'monitor', 'pacient_id' => $user->id, 'task_time' => date('Y-m-d 23:59:59', time())])->one();
                    if (empty($ut)) {
                        $ut = new UserTasks();
                        $ut->pacient_id = $user->id;
                        $ut->monitor_id = $monitors[$i];
                        $ut->type = 'monitor';
                        $ut->task_time = date('Y-m-d 23:59:59', time());
                        $ut->name = $dm->name;
                        $ut->icon = $dm->icon;
                        $ut->description = $dm->description;
                        $ut->save();
                    }
                }
            }
        } 
        
        $this->updateTasks($user);
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        return $data;
    }
    
    public function actionUpdatePacientMonitors() {
        
        $user = \Yii::$app->user->identity;
        $params = Yii::$app->request->post();
        $pacient_id = $params['pacient_id'];
        $monitors = $params['monitors'];
        $task = $params['task'];
        
        $pd = PatientDoctor::find()->where(['patient_id' => $pacient_id, 'doctor_id' => $user->id])->one();
        
        if (empty($pd)) {
            throw new \yii\web\HttpException(400, 'Вы не являетесь лечащим врачом данного пациента!', User::ERROR_ACCESS_DENIED);
        }
        
        if ($task) {
            $mm = MonitorsToUsers::find()->where(['user_id' => $pacient_id])->all();
        
            for ($i=0;$i<count($mm);$i++) {
                $dm = DicMonitors::find()->where(['id' => $mm[$i]->monitor_id, 'isTask' => 1])->one();
                if (!empty($dm)) {
                    if (!in_array($mm[$i]->monitor_id, $monitors)) {
                        $mm[$i]->delete();
                        $ut = UserTasks::find()->where(['monitor_id' => $mm[$i]->monitor_id, 'type' => 'monitor', 'pacient_id' => $pacient_id, 'task_time' => date('Y-m-d 23:59:59', time())])->one();
                        if (!empty($ut)) {
                            $ut->delete();
                        }
                    }
                }
            }
        } else {
            $mm = MonitorsToUsers::find()->where(['user_id' => $pacient_id])->all();
        
            for ($i=0;$i<count($mm);$i++) {
                if (!in_array($mm[$i]->monitor_id, $monitors)) {
                    $mm[$i]->delete();
                    $ut = UserTasks::find()->where(['monitor_id' => $mm[$i]->monitor_id, 'type' => 'monitor', 'pacient_id' => $pacient_id, 'task_time' => date('Y-m-d 23:59:59', time())])->one();
                    if (!empty($ut)) {
                        $ut->delete();
                    }
                }
            }
        }
        
        for ($i=0;$i<count($monitors);$i++) {
            
            $dm = DicMonitors::find()->where(['id' => $monitors[$i]])->one();
            if (!empty($dm)) {
                
                $mtu = MonitorsToUsers::find()->where(['user_id' => $pacient_id, 'monitor_id' => $monitors[$i]])->one();
                if (empty($mtu)) {
                    $mtu = new MonitorsToUsers();
                    $mtu->monitor_id = $monitors[$i];
                    $mtu->user_id = $pacient_id;
                    if ($dm->enter_type == 'manual') {
                        $mtu->source = 'manual';
                    }
                    $mtu->save();
                }
                if ($dm->isTask == 1) {
                    $ut = UserTasks::find()->where(['monitor_id' => $monitors[$i], 'type' => 'monitor', 'pacient_id' => $pacient_id, 'task_time' => date('Y-m-d 23:59:59', time())])->one();
                    if (empty($ut)) {
                        $ut = new UserTasks();
                        $ut->pacient_id = $pacient_id;
                        $ut->monitor_id = $monitors[$i];
                        $ut->type = 'monitor';
                        $ut->task_time = date('Y-m-d 23:59:59', time());
                        $ut->name = $dm->name;
                        $ut->icon = $dm->icon;
                        $ut->description = $dm->description;
                        $ut->save();
                    }
                }
            }
        }

        $pacient = User::find()->where(['id' => $pacient_id])->one();
        $this->updateTasks($pacient);
//        $mm = MonitorsToUsers::find()->where(['user_id' => $pacient_id])->all();
//        
//        for ($i=0;$i<count($mm);$i++) {
//            $mm[$i]->delete();
//        }
//        
//        for ($i=0;$i<count($monitors);$i++) {
//            
//            $dm = DicMonitors::find()->where(['id' => $monitors[$i]])->one();
//            if (!empty($dm)) {
//                $mtu = new MonitorsToUsers();
//                $mtu->monitor_id = $monitors[$i];
//                $mtu->user_id = $pacient_id;
//                if ($dm->enter_type == 'manual') {
//                    $mtu->source = 'manual';
//                }
//                $mtu->save();
//            }
//        }
        
        $this->sendPush('Новая задача', $user, $pacient);
        $data = new \stdClass();
        $data->success = true;
        $data->status = 200;
        return $data;
    }
    
    public function actionSetSource() {
        
        $user = \Yii::$app->user->identity;
        $params = Yii::$app->request->get();
        
        $monitor_id = $params['monitor_id'];
        $source = $params['source'];
        
        $mm = MonitorsToUsers::find()->where(['user_id' => $user->id, 'monitor_id' => $monitor_id])->one();
        
        if (!empty($mm)) {
            $mm->source = $source;
            if ($mm->save()) {
                $data = new \stdClass();
                $data->success = true;
                $data->status = 200;
                return $data;
            } else {
                throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
            }
        } else {
            throw new \yii\web\HttpException(400, sprintf('Монитор не найден в системе!'), Client::ERROR_UNKNOWN);
        }
    }
    
    private function getNorms($user, $monitor_id) {
        
        $ums = UserMonitors::find()->where(['user_id' => $user->id, 'monitor_id' => $monitor_id])->all();
        if (empty($ums)) {
            $mm = DicMonitors::find()->where(['id' => $monitor_id])->one();
            if (!empty($mm)) {
                $obj = new \stdClass();
                $obj->norm1 = $mm->norm1;
                $obj->norm2 = $mm->norm2;
                return $obj;
            } else {
                $obj = new \stdClass();
                $obj->norm1 = null;
                $obj->norm2 = null;
                return $obj;
            }
        } else {
            $obj = new \stdClass();
            $obj->norm1 = $ums->norm1;
            $obj->norm2 = $ums->norm2;
            return $obj;
        }
    }
    
    public function actionEnterMonitorParam() {
        
        $user = \Yii::$app->user->identity;
        $params = Yii::$app->request->get();
        
        $monitor_id = $params['monitor_id'];
        $param = $params['param'];
        
        $monitor = DicMonitors::find()->where(['id' => $monitor_id])->one();
        
        if (!empty($monitor)) {
            $date = date('Y-m-d', time());
            $um = UserMonitors::find()->where(['monitor_id' => $monitor_id, 'user_id' => $user->id, 'date' => $date])->one();
            if (empty($um)) {
                $um = new UserMonitors();
                $um->user_id = $user->id;
                $um->monitor_id = $monitor_id;
                $um->data = $param;
                $um->date = $date;
                $norms = $this->getNorms($user, $monitor_id);
                $um->norm1 = $norms->norm1;
                $um->norm2 = $norms->norm2;
                if ($user->save()) {
                    $data = new \stdClass();
                    $data->success = true;
                    $data->status = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
                }
            } else {
                $um->data = $param;
                if ($user->save()) {
                    $data = new \stdClass();
                    $data->success = true;
                    $data->status = 200;
                    return $data;
                } else {
                    throw new \yii\web\HttpException(400, sprintf('Неизвестная ошибка в системе!'), Client::ERROR_UNKNOWN);
                }
            }
        } else {
            throw new \yii\web\HttpException(400, sprintf('Монитор не найден в системе!'), Client::ERROR_UNKNOWN);
        }
    }
    
    public function actionGetPatientHistory() {
        
        $user = \Yii::$app->user->identity;
        
        $userAssigned = Yii::$app->authManager->getAssignments($user->id);
        $isPacient = false;
        foreach($userAssigned as $userAssign){
            if ($userAssign->roleName == 'pacient') {
                $isPacient = true;
            }
        }
        
        if (!$isPacient) {
            throw new \yii\web\HttpException(400, 'Вы не пациент!', User::ERROR_ACCESS_DENIED);
        }
        
        $notes = PatientDoctor::find()->where(['patient_id' => $user->id])->orderBy(['time' => SORT_DESC])->all();
        
        $doctors = [];
        for ($i=0;$i<count($notes);$i++) {
            $dd = User::find()->where(['id' => $notes[$i]->doctor_id])->one();
            if (!empty($dd)) {
                $profile = $dd->getPublicProfile();
                $profile->date = $notes[$i]->time;
                $profile->note_status = $notes[$i]->status;
                array_push($doctors, $profile);
            }
        }
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['doctors'] = $doctors;
        return $data;
    }
    
    public function actionGetPicture() {
  	
        $src='';
        
        $params = Yii::$app->request->get();
        
        $type=$params['type'];
        $id=$params['id'];
        $filename=$params['filename'];
        $width=$params['width'];
        
        if($type == 'avatar') {
            $src = 'uploads/users/'.$filename;
        } else if($type == 'analis') {
            $src = 'uploads/user_analisys/'.$filename;
        } else if($type == 'message') {
            $src = 'uploads/messages/'.$filename;
        } else if($type == 'pubStories') {
            $src = 'uploads/pubs/photos/stories/'.$id.'/'.$filename;
        } else if($type == 'pubPersonal') {
            $src = 'uploads/pubs/photos/personal/'.$id.'/'.$filename;
        } else if($type == 'pubFood') {
            $src = 'uploads/pubs/photos/food/'.$id.'/'.$filename;
        } else if($type == 'pubCategory') {
            $src = 'uploads/pub-category/'.$id.'/'.$filename;
        } else if($type == 'pubDrink') {
            $src = 'uploads/drink/'.$filename;
        } else if($type == 'client') {
            $src = 'uploads/clients/'.$filename;
        } else if($type == 'award') {
            $src = 'uploads/awards/'.$filename;
        }
        
//        return $src;
  	if(file_exists($src)) {
        $image=$src;
        $size = getimagesize($image);
        $width2=$size[0];
        $height=$size[1];
        $newwidth=$width;
        $scale=$width2/$newwidth;
        $newheight=$height/$scale;
        if (exif_imagetype($src) == IMAGETYPE_GIF) {
            $image = imagecreatefromgif($image);
            $thumbImage = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresized($thumbImage, $image, 0, 0, 0, 0, $newwidth, $newheight, $width2, $height);
            imagedestroy($image);
            //imagedestroy($thumbImage); do not destroy before display :)
            ob_end_clean();
            header('Content-Type: image/gif');
            imagegif($thumbImage);
            imagedestroy($thumbImage);
        }
        else if (exif_imagetype($src) == IMAGETYPE_JPEG) {

            $image = imagecreatefromjpeg($image);
            $thumbImage = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresized($thumbImage, $image, 0, 0, 0, 0, $newwidth, $newheight, $width2, $height);
            imagedestroy($image);
            //imagedestroy($thumbImage); do not destroy before display :)
            ob_end_clean();
            header('Content-Type: image/jpeg');
            imagejpeg($thumbImage);
            imagedestroy($thumbImage);
        }
        else if (exif_imagetype($src) == IMAGETYPE_PNG) {
            $image = imagecreatefrompng($image);
            $thumbImage = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresized($thumbImage, $image, 0, 0, 0, 0, $newwidth, $newheight, $width2, $height);
            imagedestroy($image);
            //imagedestroy($thumbImage); do not destroy before display :)
            ob_end_clean();
            header('Content-Type: image/png');
            imagepng($thumbImage);
            imagedestroy($thumbImage);
        }
  	} else {
//            throw new \yii\web\HttpException(400, sprintf('Ошибка доступа!'), Client::ERROR_ACCESS_DENIED);
            return 'www';
        }
    }
    
    public function actionGetSpecs() {
        $specs = DicSpecialities::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['specs'] = $specs;
        return $data;
    }
    
    public function actionGetDegree() {
        $specs = DicRanks::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['degree'] = $specs;
        return $data;
    }
    
    public function actionGetCategory() {
        $specs = DicDigrees::find()->all();
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['categories'] = $specs;
        return $data;
    }

    public function actionSetNotification() {
        
        $user = \Yii::$app->user->identity;
        $params = Yii::$app->request->get();
        $send_push = $params['send_push'];
        
        $user->send_push = $send_push;
        $user->save();

        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        return $data;
    }

    private function sendPush($text, $from, $to)
    {        
        $u = 'https://onesignal.com/api/v1/notifications';
        $ch = curl_init();
        
        $data = new \stdClass();
        $data->app_id = User::APP_ID;
        $contents = new \stdClass();
        $contents->en = $text;
        $contents->ru = $text;
        $data->contents = $contents;
        
        $body = new \stdClass();
//        $body->profile = $from->getPublicProfile();
        $body->id = $from->id;
        $body->fio = $from->fio;
        $body->avatar = $from->avatar;
        $body->specialities = [];
        $body->type = 'task';
        $data->data = $body;
        
        // $headings = new \stdClass();
        // $headings->en = $title;
        // $headings->ru = $title;
        // $data->headings = $headings;
        
        $user = User::find()->where(['id' => $to->id])->one();
        
        if (!empty($user)) {
            $ids = [];
            array_push($ids, $user->push_id);
            $data->include_player_ids = $ids;

            $data_string = json_encode($data);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
                'Authorization: Basic '.User::APP_TOKEN
            )); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_URL, $u);
            $u = trim(curl_exec($ch));
//            print_r($u);
            curl_close($ch);
        }
    }
}