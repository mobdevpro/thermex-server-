<?php
namespace api\modules\v1\controllers;

use yii;
use yii\web\Response;
use common\models\User;

/**
 * User Controller
 */
class HelperController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\Vendors';
    
    var $unauthorized_actions = [
            'geocoder',
        ];
    
    public function behaviors() {
        $behaviors = parent::behaviors();
//        $behaviors['authenticator']['class'] = HttpBearerAuth::className();
        $behaviors['authenticator']['except'] = $this->unauthorized_actions;
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    public function init() {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }
    
    public function actionGeocoder() {
        
        $params = Yii::$app->request->get();
        
        if(!empty($params['query'])) {
            //&kind=house
            $response = file_get_contents('https://geocode-maps.yandex.ru/1.x?format=json&apikey=cebfb0ca-63b3-4f1d-a7b5-4ca90129f140&geocode='.$params['query']);
            $response = json_decode($response);
//            print_r($response->response->GeoObjectCollection->featureMember);
            $array = $response->response->GeoObjectCollection->featureMember;
            if(count($array)) {
                $name = $array[0]->GeoObject->metaDataProperty->GeocoderMetaData->text;
                $data['success'] = true;
                $data['status'] = 200;
                $data['address'] = $name;
                return $data;
            } else {
                throw new \yii\web\HttpException(400, 'Ничего не найдено!', User::ERROR_BAD_DATA);
            }
        } else {
            throw new \yii\web\HttpException(400, 'Укажите запрос!', User::ERROR_BAD_DATA);
        }
    }
}