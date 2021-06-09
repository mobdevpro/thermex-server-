<?php

namespace console\controllers;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use console\components\ChatServer;
use Eole\Sandstone;
use console\components\Singleton;
use console\components\SingletonQueue;
use console\components\SingletonUser;

class ChatController extends \yii\console\Controller
{
    var $singleton;
    var $singletonQueue;
    var $singletonUser;

    public function actionStartSocket($port=503)
    {
        $this->singleton = Singleton::getInstance();
        $this->singletonQueue = SingletonQueue::getInstance();
        $this->singletonUser = SingletonUser::getInstance();

        $chatServer = new ChatServer();
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $chatServer
                )
            ),
            $port
        );

        $server->loop->addPeriodicTimer(0.1, function () use (&$server, $chatServer) {   
            
            $task = $this->singletonUser->current();
            if ($task != null) {
                if (array_key_exists($task->socket->resourceId, $this->singletonQueue->user->socket)) {
                    $msg = new \stdClass();
                    $msg->success = false;
                    $msg->device = $task->data['data']['device'];
                    $msg->address = $task->data['data']['address'];
                    $task->socket->send(json_encode($msg));
                    $this->singletonUser->extract();
                    // if ($chatServer->users[$task->socket->resourceId]->command == null) {
                    //     if ($task->command == 'read') {
                    //         echo 'addPeriodicTimer count: '.$this->singleton->count().PHP_EOL;
                    //         $chatServer->modems[$task->socket->resourceId]->command = $task;
                    //         $task->socket->send(hex2bin($task->data));
                    //         echo 'send to '.$task->device->name_our.' data: '.$task->data.PHP_EOL;
                    //     } else {
                    //         echo 'task write'.PHP_EOL;
                    //         $this->singleton->extract();
                    //     }
                    // }
                }
            }
        });

        $server->run();
    }
}
