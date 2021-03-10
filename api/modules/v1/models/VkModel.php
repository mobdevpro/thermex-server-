<?php
namespace api\modules\v1\models;

use yii\base\Model;

/**
 * User Model
 */
class VkModel extends Model
{
    public $token;
    public $user_id;
    public $first_name;
    public $last_name;
    public $birthday;
    
    public function __construct($config = []) {
        
        parent::__construct($config);
    }
    
    public function init() {
 
        parent::init();
    }
    
    public function callMethod($method, $parameters)
    {
        if (!$this->token) {
            return false;
        }
        $parameters['v'] = '5.52';
        if (is_array($parameters)) {
            $parameters = http_build_query($parameters);
        }
        $queryString = "/method/$method?$parameters&access_token={$this->token}";
//        $querySig = md5($queryString.$this->accessSecret);
        $url = "https://api.vk.com{$queryString}&sig=$queryString";
        //var_dump($url);
        return file_get_contents($url);
    }
    
    public function getUserProfile() {
        
        $url = 'https://api.vk.com/method/account.getProfileInfo?access_token='.$this->token;
        echo $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS,
//                        'Http_username='.urlencode($login).'&Http_password='.urlencode($password).'&Phone_list='.$phone.'&Message='.urlencode($text));
        curl_setopt($ch, CURLOPT_URL, $url);
        $u = trim(curl_exec($ch));
        curl_close($ch);
        
        return json_decode($u);
    }
}