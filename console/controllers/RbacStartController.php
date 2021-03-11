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

       // // добавляем роль "watcher"
       // $watcher = $auth->createRole('watcher');
       // $auth->add($watcher);

       // // добавляем роль "manager"
       // $manager = $auth->createRole('manager');
       // $auth->add($manager);

       // // добавляем роль "user"
       // $user = $auth->createRole('user');
       // $auth->add($user);

       // // добавляем роль "engineer"
       // $engineer = $auth->createRole('engineer');
       // $auth->add($engineer);

    //    // добавляем роль "partner"
    //    $partner = $auth->createRole('partner');
    //    $auth->add($partner);
       
       // $auth->addChild($admin, $manager);
       // $auth->addChild($admin, $user);
       // $auth->addChild($admin, $engineer);
    //    $auth->addChild($admin, $partner);
    //    $auth->addChild($admin, $watcher);
       
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

        //    $getDb = $auth->createPermission('getDb');
        //    $getDb->description = 'Получение списка/одной баз(ы) данных';
        //    $auth->add($getDb);

        //    $updateDb = $auth->createPermission('updateDb');
        //    $updateDb->description = 'Создание/редактирование/удаление базы данных';
        //    $auth->add($updateDb);

    //    $getSettings = $auth->createPermission('getSettings');
    //    $getSettings->description = 'Получение списка настроек системы';
    //    $auth->add($getSettings);

    //    $updateSettings = $auth->createPermission('updateSettings');
    //    $updateSettings->description = 'Редактирование настройки';
    //    $auth->add($updateSettings);

        // $getEngineers = $auth->createPermission('getEngineers');
        // $getEngineers->description = 'Получение списка инженеров';
        // $auth->add($getEngineers);

        // $updateEngineer = $auth->createPermission('updateEngineer');
        // $updateEngineer->description = 'Создание/редактирование профиля инженера';
        // $auth->add($updateEngineer);

        // $deleteEngineer = $auth->createPermission('deleteEngineer');
        // $deleteEngineer->description = 'Удаление профиля инженера';
        // $auth->add($deleteEngineer);

        // $getManagers = $auth->createPermission('getManagers');
        // $getManagers->description = 'Получение списка менеджеров';
        // $auth->add($getManagers);

        // $updateManager = $auth->createPermission('updateManager');
        // $updateManager->description = 'Создание/редактирование профиля менеджера';
        // $auth->add($updateManager);

        // $deleteManager = $auth->createPermission('deleteManager');
        // $deleteManager->description = 'Удаление профиля менеджера';
        // $auth->add($deleteManager);

        $getPartners = $auth->createPermission('getPartners');
        $getPartners->description = 'Получение списка партнеров';
        $auth->add($getPartners);

        $updatePartner = $auth->createPermission('updatePartner');
        $updatePartner->description = 'Создание/редактирование профиля партнера';
        $auth->add($updatePartner);

        $deletePartner = $auth->createPermission('deletePartner');
        $deletePartner->description = 'Удаление профиля партнера';
        $auth->add($deletePartner);



       $auth->addChild($admin, $getPartners);
       $auth->addChild($admin, $updatePartner);
       $auth->addChild($admin, $deletePartner);
   }
}