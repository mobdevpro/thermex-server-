<?php

namespace api\modules\v1\components;

use yii\filters\auth\AuthMethod;
use common\models\User;

class CustomAuth extends AuthMethod
{   
    /**
     * @inheritdoc
     */
    public function authenticate($user, $request, $response)
    {
        if(!empty($request->headers['authorization'])) {
            $auth = $request->headers['authorization'];
            $auth = explode(' ', $auth);
            if(count($auth) == 2) {
                if(strlen($auth[1]) == 0) {
                    return null;
                }
                $user = User::findIdentityByAccessToken($auth[1]);
                if($user) {
                    \Yii::$app->user->identity = $user;
                    return $user;
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}