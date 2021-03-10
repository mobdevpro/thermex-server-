<?php
namespace api\modules\v1\components;

use yii\filters\auth\QueryParamAuth;
use yii\filters\Cors;

/**
 * API Base Controller
 * All controllers within API app must extend this controller!
 */
class ApiController extends \yii\rest\ActiveController
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // add CORS filter
//        $behaviors['corsFilter'] = [
//            'class' => Cors::className(),
//            'cors' => [
//                'Origin' => ['http://localhost:3000', 'http://localhost:80'],
//                'Access-Control-Allow-Origin' => '*',
//                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
//                'Access-Control-Allow-Credentials' => true,
//                'Access-Control-Allow-Headers' => ['Accept','Authorization', 'DNT','Content-Type', 'Referer', 'Origin', '*'],
//                'Access-Control-Max-Age'           => 3600,
//            ],
//        ];
        
        // add QueryParamAuth for authentication
        $behaviors['authenticator'] = [
            'class' => QueryParamAuth::className(),
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

}