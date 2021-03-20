<?php
namespace api\modules\v1\controllers;

use yii;
use yii\web\Response;
use common\models\User;
use common\models\Locations;

/**
 * User Controller
 */
class HelperController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\Locations';
    
    var $unauthorized_actions = [
            'geocoder',
            'search-address'
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

    public function actionSearchAddress() {
        
        $params = Yii::$app->request->get();
        $address = $params['address'];
        if(strlen($address) >= 3) {
            
            $array = [];
            $address = Locations::find()->where(['like', 'name', $address])->all();

            for ($i=0;$i<count($address);$i++) {
                if ($address[$i]->level == 1) {
                    $obj = new \stdClass();
                    $obj->id = $address[$i]->id;
                    $obj->timezone = $address[$i]->timezone;
                    $obj->fias = $address[$i]->fias;
                    $obj->name = $address[$i]->name;
                    array_push($array, $obj);
                } else if ($address[$i]->level == 2) {
                    $owner = Locations::find()->where(['id' => $address[$i]->owner])->one();
                    $obj = new \stdClass();
                    $obj->id = $address[$i]->id;
                    $obj->timezone = $address[$i]->timezone;
                    $obj->fias = $address[$i]->fias;
                    $obj->name = $owner->name.', '.$address[$i]->name;
                    array_push($array, $obj);
                } else if ($address[$i]->level == 3) {
                    $owner = Locations::find()->where(['id' => $address[$i]->owner])->one();
                    $owner2 = Locations::find()->where(['id' => $owner->owner])->one();
                    $obj = new \stdClass();
                    $obj->id = $address[$i]->id;
                    $obj->timezone = $address[$i]->timezone;
                    $obj->fias = $address[$i]->fias;
                    $obj->name = $owner2->name.', '.$owner->name.', '.$address[$i]->type.' '.$address[$i]->name;
                    array_push($array, $obj);
                }
            }

            $data['success'] = true;
            $data['status'] = 200;
            $data['address'] = $array;
            return $data;
        } else {
            $data['success'] = true;
            $data['status'] = 200;
            $data['address'] = [];
            return $data;
        }
    }
}