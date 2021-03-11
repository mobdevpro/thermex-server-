<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;

class RbacStartController extends Controller
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;

       // // добавляем роль "admin"
       // $admin = $auth->createRole('admin');
       // $auth->add($admin);

       // // добавляем роль "manager"
       // $manager = $auth->createRole('manager');
       // $auth->add($manager);

       // // добавляем роль "user"
       // $user = $auth->createRole('user');
       // $auth->add($user);

       // // добавляем роль "engineer"
       // $engineer = $auth->createRole('engineer');
       // $auth->add($engineer);
       
       // $auth->addChild($admin, $manager);
       // $auth->addChild($admin, $user);
       // $auth->addChild($admin, $engineer);
       
       // $auth = Yii::$app->authManager;
       // $role = $auth->getRole('admin');
       // $auth->assign($role, 1);
       
       $admin = $auth->getRole('admin');

       // $getPermissions = $auth->createPermission('getPermissions');
       // $getPermissions->description = 'Получение списка разрешений';
       // $auth->add($getPermissions);

       // $updatePermission = $auth->createPermission('updatePermission');
       // $updatePermission->description = 'Изменение разрешений для пользователей';
       // $auth->add($updatePermission);
         
       // $auth->addChild($admin, $getPermissions);
       // $auth->addChild($admin, $updatePermission);

       $getDb = $auth->createPermission('getDb');
       $getDb->description = 'Получение списка/одной баз(ы) данных';
       $auth->add($getDb);

       $updateDb = $auth->createPermission('updateDb');
       $updateDb->description = 'Создание/редактирование/удаление базы данных';
       $auth->add($updateDb);

       $auth->addChild($admin, $getDb);
       $auth->addChild($admin, $updateDb);
   }
}