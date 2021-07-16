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
    // var $singleton;
    var $singletonQueue;
    var $singletonUser;
    var $currentTime;

    public function actionStartSocket($key=0, $port1=502, $port2=503)
    {
        // $this->singleton = Singleton::getInstance();
        $this->singletonQueue = SingletonQueue::getInstance();
        $this->singletonUser = SingletonUser::getInstance();
        $this->currentTime = -1;

        $loop = \React\EventLoop\Factory::create();

        $address1 = '0.0.0.0';

        $socket1 = new \React\Socket\Server($address1.':'.$port1, $loop);
        $socket2 = new \React\Socket\Server($address1.':'.$port2, $loop);

        $socketServer = new SocketServer($key);
        $clientServer = new IoServer($socketServer, $socket1, $loop);

        $chatServer = new ChatServer();
        $wsServer = new IoServer(new HttpServer(new WsServer($chatServer)), $socket2, $loop);

        $loop->addPeriodicTimer(0.1, function () use (&$clientServer, $socketServer) {   
            
            foreach ($this->singletonQueue->dev->socket as $key => $value) {
                $task = $value->queue->current();
                if ($task != null) {
                    if ($task->time <= time()) {
                        // if (time() - $task->time > 20) {
                        //     $value->queue->extract();
                        // } else {
                            if ($task->command == 'read' || $task->command == 'alarm') {
                                if (array_key_exists($task->socket->resourceId, $this->singletonQueue->dev->socket)) {
                                    if ($this->singletonQueue->dev->socket[$task->socket->resourceId]->command == null) {
                                        $this->currentTime = time();
                                        echo 'addPeriodicTimer count: '.$value->queue->count().PHP_EOL;
                                        $this->singletonQueue->dev->socket[$task->socket->resourceId]->command = $task;
                                        $task->socket->send(hex2bin($task->data));
                                        echo 'send to '.$task->device->name_our.' data: '.$task->data.PHP_EOL;
                                    }
                                }
                            } else if ($task->command == 'write') {
                                if (array_key_exists($task->socket->resourceId, $this->singletonQueue->dev->socket)) {
                                    if ($this->singletonQueue->dev->socket[$task->socket->resourceId]->command == null) {
                                        $this->currentTime = time();
                                        echo 'send write to '.$task->device->name_our.' data: '.$task->data.PHP_EOL;
                                        $this->singletonQueue->dev->socket[$task->socket->resourceId]->command = $task;
                                        $this->singletonQueue->dev->dev[$task->device->id]->socket->send(hex2bin($task->data));
                                    }
                                }
                            }
                        // }
                    }
                }
            }
        });

        $loop->run();
    }
}
