<?php

namespace console\controllers;

use yii;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use console\components\SocketServer;
use Ratchet\Http\HttpServer;
use console\components\ChatServer;
use console\components\Singleton;
use console\components\SingletonQueue;
use console\components\SingletonUser;
use Eole\Sandstone;
use common\models\Device;
use common\models\Firmware;

class ModbusController extends \yii\console\Controller
{
    var $singleton;
    var $singletonQueue;
    var $singletonUser;

    public function actionStartSocket($key=0, $port1=502, $port2=503)
    {
        $this->singleton = Singleton::getInstance();
        $this->singletonQueue = SingletonQueue::getInstance();
        $this->singletonUser = SingletonUser::getInstance();

        $loop = \React\EventLoop\Factory::create();

        $address1 = '0.0.0.0';

        $socket1 = new \React\Socket\Server($address1.':'.$port1, $loop);
        // $socket1->listen($port1, $address1);

        $address2 = '127.0.0.1';

        $socket2 = new \React\Socket\Server($address1.':'.$port2, $loop);
        // $socket2->listen($port2, $address2);

        $socketServer = new SocketServer($key);
        $clientServer = new IoServer($socketServer, $socket1, $loop);

        $chatServer = new ChatServer();
        $wsServer = new IoServer(new HttpServer(new WsServer($chatServer)), $socket2, $loop);

        $loop->addPeriodicTimer(0.1, function () use (&$clientServer, $socketServer) {   
            
            $task = $this->singleton->current();
            if ($task != null) {
                if ($task->time <= time()) {
                    if ($task->command == 'read') {
                        if (array_key_exists($task->socket->resourceId, $this->singletonQueue->dev->socket)) {
                            if ($this->singletonQueue->dev->socket[$task->socket->resourceId]->command == null) {
                                echo 'addPeriodicTimer count: '.$this->singleton->count().PHP_EOL;
                                $this->singletonQueue->dev->socket[$task->socket->resourceId]->command = $task;
                                $task->socket->send(hex2bin($task->data));
                                echo 'send to '.$task->device->name_our.' data: '.$task->data.PHP_EOL;
                            }
                        }
                    } else {
                        if (array_key_exists($task->socket->resourceId, $this->singletonQueue->dev->socket)) {
                            if ($this->singletonQueue->dev->socket[$task->socket->resourceId]->command == null) {
                                echo 'write to '.$task->device->name_our.' data: '.$task->data.PHP_EOL;
                                $this->singletonQueue->dev->socket[$task->socket->resourceId]->command = $task;
                                $this->singletonQueue->dev->dev[$task->device->id]->socket->send(hex2bin($task->data));
                                // $this->singleton->extract();
                            }
                        }
                    }
                }
            }
        });

        // $loop->addPeriodicTimer(0.1, function () use (&$wsServer, $chatServer) {   
            
        //     $task = $this->singletonUser->current();
        //     if ($task != null) {
        //         if (array_key_exists($task->socket->resourceId, $this->singletonQueue->user->socket)) {
        //             $msg = new \stdClass();
        //             $msg->success = false;
        //             $msg->device = $task->data['data']['device'];
        //             $msg->address = $task->data['data']['address'];
        //             $task->socket->send(json_encode($msg));
        //             $this->singletonUser->extract();
        //             // if ($chatServer->users[$task->socket->resourceId]->command == null) {
        //             //     if ($task->command == 'read') {
        //             //         echo 'addPeriodicTimer count: '.$this->singleton->count().PHP_EOL;
        //             //         $chatServer->modems[$task->socket->resourceId]->command = $task;
        //             //         $task->socket->send(hex2bin($task->data));
        //             //         echo 'send to '.$task->device->name_our.' data: '.$task->data.PHP_EOL;
        //             //     } else {
        //             //         echo 'task write'.PHP_EOL;
        //             //         $this->singleton->extract();
        //             //     }
        //             // }
        //         }
        //     }
        // });

        $loop->run();

        // return;

        // $socketServer = new SocketServer($key);
        // $server = IoServer::factory(
        //     $socketServer,
        //     $port1
        // );
 
        // $server->loop->addPeriodicTimer(0.1, function () use (&$server, $socketServer) {   
            
        //     $task = $this->singleton->current();
        //     if ($task != null) {
        //         if ($task->time <= time()) {
        //             if (array_key_exists($task->socket->resourceId, $this->singletonQueue->dev->socket)) {
        //                 if ($this->singletonQueue->dev->socket[$task->socket->resourceId]->command == null) {
        //                     if ($task->command == 'read') {
        //                         echo 'addPeriodicTimer count: '.$this->singleton->count().PHP_EOL;
        //                         $this->singletonQueue->dev->socket[$task->socket->resourceId]->command = $task;
        //                         $task->socket->send(hex2bin($task->data));
        //                         echo 'send to '.$task->device->name_our.' data: '.$task->data.PHP_EOL;
        //                     } else {
        //                         echo 'task write'.PHP_EOL;
        //                         $this->singleton->extract();
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // });

        // $server->run();

        // $chatServer = new ChatServer();
        // $server2 = IoServer::factory(
        //     new HttpServer(
        //         new WsServer(
        //             $chatServer
        //         )
        //     ),
        //     $port2
        // );

       

        // $server2->run();
    }
}
