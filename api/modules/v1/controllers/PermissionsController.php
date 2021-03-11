<?php
namespace api\modules\v1\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;
use common\models\User;
use common\models\Databases;

/**
 * Permissions Controller
 */
class PermissionsController extends \api\modules\v1\components\ApiController
{
    public $modelClass = 'api\modules\v1\models\Databases';
    
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
    
    public function actionGetPermissions() {
        
        if (!\Yii::$app->user->can('getPermissions')) {
            throw new \yii\web\HttpException(401, 'Операция разрешена администратору!', User::ERROR_ACCESS_DENIED);
        }
        
        $array = [];
        $roles = Yii::$app->authManager->getRoles();
        $permissions = Yii::$app->authManager->getPermissions();
//        print_r($permissions);die;
        foreach ($permissions as $name => $permission) {
            $rr = new \stdClass();
            foreach ($roles as $rolesName => $value) {
                $has = false;
                $permsForRole = Yii::$app->authManager->getPermissionsByRole($rolesName);
                foreach ($permsForRole as $key => $value) {
                    if ($key == $name) {
                        $has = true;
                        break;
                    }
                }
                $rr->{$rolesName} = $has;
            }
            $rr->name = $name;
            $rr->description = $permission->description;
            array_push($array, $rr);
        }
        
        $data = [];
        $data['success'] = true;
        $data['status'] = 200;
        $data['permissions'] = $array;
        return $data;
    
    }
    
    public function actionUpdatePermission() {
        
        if (!\Yii::$app->user->can('updatePermission')) {
            throw new \yii\web\HttpException(401, 'Операция разрешена администратору!', User::ERROR_ACCESS_DENIED);
        }
        
        $params = Yii::$app->request->post();
        
        $permission = $params['permission'];
        $role = $params['role'];
        $value = $params['value'];
        
        $role2 = Yii::$app->authManager->getRole($role);
        $permission2 = Yii::$app->authManager->getPermission($permission);
        if ($value) {
            if (Yii::$app->authManager->canAddChild($role2, $permission2)) {
                if (Yii::$app->authManager->addChild($role2, $permission2)) {
                    $data = [];
                    $data['success'] = true;
                    $data['status'] = 200;
                    return $data;
                } else {
                    $data = [];
                    $data['success'] = false;
                    $data['status'] = 200;
                    return $data;
                }
            } else {
                $data = [];
                $data['success'] = false;
                $data['status'] = 200;
                return $data;
            }
        } else {
            if (Yii::$app->authManager->removeChild($role2, $permission2)) {
                $data = [];
                $data['success'] = true;
                $data['status'] = 200;
                return $data;
            } else {
                $data = [];
                $data['success'] = false;
                $data['status'] = 200;
                return $data;
            }
        }
    }
}