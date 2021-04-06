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

        // $getPartners = $auth->createPermission('getPartners');
        // $getPartners->description = 'Получение списка партнеров';
        // $auth->add($getPartners);

        // $updatePartner = $auth->createPermission('updatePartner');
        // $updatePartner->description = 'Создание/редактирование профиля партнера';
        // $auth->add($updatePartner);

        // $deletePartner = $auth->createPermission('deletePartner');
        // $deletePartner->description = 'Удаление профиля партнера';
        // $auth->add($deletePartner);

        // $getModels = $auth->createPermission('getModels');
        // $getModels->description = 'Получение списка моделей контроллеров';
        // $auth->add($getModels);

        // $updateModel = $auth->createPermission('updateModel');
        // $updateModel->description = 'Создание/редактирование модели контролера';
        // $auth->add($updateModel);

        // $deleteModel = $auth->createPermission('deleteModel');
        // $deleteModel->description = 'Удаление модели контролера';
        // $auth->add($deleteModel);

        // $getDevices = $auth->createPermission('getDevices');
        // $getDevices->description = 'Получение списка устройств';
        // $auth->add($getDevices);

        // $updateDevice = $auth->createPermission('updateDevice');
        // $updateDevice->description = 'Создание/редактирование устройства';
        // $auth->add($updateDevice);

        // $deleteDevice = $auth->createPermission('deleteDevice');
        // $deleteDevice->description = 'Удаление устройства';
        // $auth->add($deleteDevice);

        // $getFirmwares = $auth->createPermission('getFirmwares');
        // $getFirmwares->description = 'Получение списка прошивок';
        // $auth->add($getFirmwares);

        // $updateFirmware = $auth->createPermission('updateFirmware');
        // $updateFirmware->description = 'Создание/редактирование прошивки';
        // $auth->add($updateFirmware);

        // $deleteFirmware = $auth->createPermission('deleteFirmware');
        // $deleteFirmware->description = 'Удаление прошивки';
        // $auth->add($deleteFirmware);

        $getEnums = $auth->createPermission('getEnums');
        $getEnums->description = 'Получение списка перечисляемых типов';
        $auth->add($getEnums);

        $updateEnum = $auth->createPermission('updateEnum');
        $updateEnum->description = 'Создание/редактирование перечисляемого типа';
        $auth->add($updateEnum);

        $deleteEnum = $auth->createPermission('deleteEnum');
        $deleteEnum->description = 'Удаление перечисляемого типа';
        $auth->add($deleteEnum);



       $auth->addChild($admin, $getEnums);
       $auth->addChild($admin, $updateEnum);
       $auth->addChild($admin, $deleteEnum);
   }
}