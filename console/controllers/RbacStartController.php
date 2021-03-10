<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;

class RbacStartController extends Controller
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;

       // добавляем роль "admin"
       $admin = $auth->createRole('admin');
       $auth->add($admin);

       // добавляем роль "manager"
       $manager = $auth->createRole('manager');
       $auth->add($manager);

       // добавляем роль "user"
       $user = $auth->createRole('user');
       $auth->add($user);

       // добавляем роль "engineer"
       $engineer = $auth->createRole('engineer');
       $auth->add($engineer);
       
       $auth->addChild($admin, $manager);
       $auth->addChild($admin, $user);
       $auth->addChild($admin, $engineer);
       
//        $updateUser = $auth->createPermission('updateUser');
//        $updateUser->description = 'Создание и редактирование пользователя';
//        $auth->add($updateUser);
//        
//        $getUnits = $auth->createPermission('getUnits');
//        $getUnits->description = 'Получение списка единиц измерения';
//        $auth->add($getUnits);
//        
//        $updateUnit = $auth->createPermission('updateUnit');
//        $updateUnit->description = 'Создание и сохранение единиц измерения';
//        $auth->add($updateUnit);
//        
//        $deleteUnit = $auth->createPermission('deleteUnit');
//        $deleteUnit->description = 'Удаление единицы измерения';
//        $auth->add($deleteUnit);
//        
//        
//        $getPermissions = $auth->createPermission('getPermissions');
//        $getPermissions->description = 'Получение списка всех разрешений и ролей';
//        $auth->add($getPermissions);
//        
//        $updatePermission = $auth->createPermission('updatePermission');
//        $updatePermission->description = 'Изменение разрешения для роли';
//        $auth->add($updatePermission);
//        
//        $newPassword = $auth->createPermission('newPassword');
//        $newPassword->description = 'Сброс пароля любого пользователя';
//        $auth->add($newPassword);
//        
//        $setUserStatus = $auth->createPermission('setUserStatus');
//        $setUserStatus->description = 'Установка статуса юзера (активный/неактивный)';
//        $auth->add($setUserStatus);
//        
//        $getSpecs = $auth->createPermission('getSpecs');
//        $getSpecs->description = 'Получение списка специальностей врачей';
//        $auth->add($getSpecs);
//        
//        $updateSpec = $auth->createPermission('updateSpec');
//        $updateSpec->description = 'Создание и сохранение специальности врача';
//        $auth->add($updateSpec);
//        
//        $deleteSpec = $auth->createPermission('deleteSpec');
//        $deleteSpec->description = 'Удаление специальности врача';
//        $auth->add($deleteSpec);
        
//        $getParts = $auth->createPermission('getParts');
//        $getParts->description = 'Получение списка разделов анкеты';
//        $auth->add($getParts);
//        
//        $updatePart = $auth->createPermission('updatePart');
//        $updatePart->description = 'Создание и сохранение раздела анкеты';
//        $auth->add($updatePart);
//        
//        $deletePart = $auth->createPermission('deletePart');
//        $deletePart->description = 'Удаление раздела анкеты';
//        $auth->add($deletePart);
        
//        $getSections = $auth->createPermission('getSections');
//        $getSections->description = 'Получение списка секций анкеты';
//        $auth->add($getSections);
//        
//        $updateSection = $auth->createPermission('updateSection');
//        $updateSection->description = 'Создание и сохранение секции анкеты';
//        $auth->add($updateSection);
//        
//        $deleteSection = $auth->createPermission('deleteSection');
//        $deleteSection->description = 'Удаление секции анкеты';
//        $auth->add($deleteSection);
        
//        $getQuestions = $auth->createPermission('getQuestion');
//        $getQuestions->description = 'Получение списка вопросов анкеты';
//        $auth->add($getQuestions);
//        
//        $updateQuestion = $auth->createPermission('updateQuestion');
//        $updateQuestion->description = 'Создание и сохранение вопроса анкеты';
//        $auth->add($updateQuestion);
//        
//        $deleteQuestion = $auth->createPermission('deleteQuestion');
//        $deleteQuestion->description = 'Удаление вопроса анкеты';
//        $auth->add($deleteQuestion);
        
//        $getAnalisys = $auth->createPermission('getAnalisys');
//        $getAnalisys->description = 'Получение списка анализов';
//        $auth->add($getAnalisys);
//        
//        $updateAnalis = $auth->createPermission('updateAnalis');
//        $updateAnalis->description = 'Создание и сохранение анализа в словаре';
//        $auth->add($updateAnalis);
//        
//        $deleteAnalis = $auth->createPermission('deleteAnalis');
//        $deleteAnalis->description = 'Удаление анализа из словаря';
//        $auth->add($deleteAnalis);
        
//        $getRanks = $auth->createPermission('getRanks');
//        $getRanks->description = 'Получение списка ученых званий';
//        $auth->add($getRanks);
//        
//        $updateRank = $auth->createPermission('updateRank');
//        $updateRank->description = 'Создание и сохранение ученого звания';
//        $auth->add($updateRank);
//        
//        $deleteRank = $auth->createPermission('deleteRank');
//        $deleteRank->description = 'Удаление ученого звания';
//        $auth->add($deleteRank);
//        
//        $getDigrees = $auth->createPermission('getDigrees');
//        $getDigrees->description = 'Получение списка категорий врачей';
//        $auth->add($getDigrees);
//        
//        $updateDigree = $auth->createPermission('updateDigree');
//        $updateDigree->description = 'Создание и сохранение категории врачей';
//        $auth->add($updateDigree);
//        
//        $deleteDigree = $auth->createPermission('deleteDigree');
//        $deleteDigree->description = 'Удаление категории врачей';
//        $auth->add($deleteDigree);
        
//        $getMonitors = $auth->createPermission('getMonitors');
//        $getMonitors->description = 'Получение списка типов мониторов';
//        $auth->add($getMonitors);
//        
//        $updateMonitor = $auth->createPermission('updateMonitor');
//        $updateMonitor->description = 'Создание и сохранение типа монитора';
//        $auth->add($updateMonitor);
//        
//        $deleteMonitor = $auth->createPermission('deleteMonitor');
//        $deleteMonitor->description = 'Удаление типа монитора';
//        $auth->add($deleteMonitor);
        
//        $getBalls = $auth->createPermission('getBalls');
//        $getBalls->description = 'Получение списка баллов разделов анкеты';
//        $auth->add($getBalls);
//        
//        $updateBall = $auth->createPermission('updateBall');
//        $updateBall->description = 'Создание и сохранение баллов анкеты';
//        $auth->add($updateBall);
//        
//        $deleteBall = $auth->createPermission('deleteBall');
//        $deleteBall->description = 'Удаление баллов анкеты';
//        $auth->add($deleteBall);
        
//        $getDiets = $auth->createPermission('getDiets');
//        $getDiets->description = 'Получение списка диет';
//        $auth->add($getDiets);
//        
//        $updateDiet = $auth->createPermission('updateDiet');
//        $updateDiet->description = 'Создание и сохранение диеты';
//        $auth->add($updateDiet);
//        
//        $deleteDiet = $auth->createPermission('deleteDiet');
//        $deleteDiet->description = 'Удаление диеты';
//        $auth->add($deleteDiet);
        
        // $getTasks = $auth->createPermission('getTasks');
        // $getTasks->description = 'Получение списка задач';
        // $auth->add($getTasks);
        
        // $updateTask = $auth->createPermission('updateTask');
        // $updateTask->description = 'Создание и сохранение задачи';
        // $auth->add($updateTask);
        
        // $deleteTask = $auth->createPermission('deleteTask');
        // $deleteTask->description = 'Удаление задачи';
        // $auth->add($deleteTask);
        
        // $admin = $auth->getRole('admin');
        
//        $auth->addChild($admin, $updateUser);
//        $auth->addChild($admin, $getUnits);
//        $auth->addChild($admin, $updateUnit);
//        $auth->addChild($admin, $deleteUnit);
//          
//        $auth->addChild($admin, $getSpecs);
//        $auth->addChild($admin, $updateSpec);
//        $auth->addChild($admin, $deleteSpec);
//        $auth->addChild($admin, $getPermissions);
//        $auth->addChild($admin, $updatePermission);
//        
//        $auth->addChild($admin, $newPassword);
//        
//        $auth->addChild($admin, $setUserStatus);
        
//        $auth->addChild($admin, $getParts);
//        $auth->addChild($admin, $updatePart);
//        $auth->addChild($admin, $deletePart);
        
//        $auth->addChild($admin, $getSections);
//        $auth->addChild($admin, $updateSection);
//        $auth->addChild($admin, $deleteSection);
        
//        $auth->addChild($admin, $getQuestions);
//        $auth->addChild($admin, $updateQuestion);
//        $auth->addChild($admin, $deleteQuestion);
        
//        $auth->addChild($admin, $getAnalisys);
//        $auth->addChild($admin, $updateAnalis);
//        $auth->addChild($admin, $deleteAnalis);
        
//        $auth->addChild($admin, $getRanks);
//        $auth->addChild($admin, $updateRank);
//        $auth->addChild($admin, $deleteRank);
//        
//        $auth->addChild($admin, $getDigrees);
//        $auth->addChild($admin, $updateDigree);
//        $auth->addChild($admin, $deleteDigree);
        
//        $auth->addChild($admin, $getMonitors);
//        $auth->addChild($admin, $updateMonitor);
//        $auth->addChild($admin, $deleteMonitor);
        
//        $auth->addChild($admin, $getBalls);
//        $auth->addChild($admin, $updateBall);
//        $auth->addChild($admin, $deleteBall);
        
//        $auth->addChild($admin, $getDiets);
//        $auth->addChild($admin, $updateDiet);
//        $auth->addChild($admin, $deleteDiet);
        
        // $auth->addChild($admin, $getTasks);
        // $auth->addChild($admin, $updateTask);
        // $auth->addChild($admin, $deleteTask);
        
       $auth = Yii::$app->authManager;
       $role = $auth->getRole('admin');
//        $role = $auth->getRole('pacient');
       $auth->assign($role, 1);
//        $auth->assign($role, 4);
//        $auth->assign($role, 5);
   }
}