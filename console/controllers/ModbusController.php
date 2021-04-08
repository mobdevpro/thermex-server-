<?php

namespace console\controllers;

use yii;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use console\components\SocketServer;
use console\components\Singleton;
use Eole\Sandstone;
use common\models\Device;
use common\models\Firmware;

class ModbusController extends \yii\console\Controller
{
    var $singleton;

    public function actionStartSocket($port=502)
    {
        Singleton::releaseInstance();
        $this->singleton = Singleton::getInstance();

        $socketServer = new SocketServer();
        $server = IoServer::factory(
            $socketServer,
            $port
        );
 
        $server->loop->addPeriodicTimer(0.1, function () use (&$server, $socketServer) {   
            
            $task = $this->singleton->current();
            if ($task != null) {
                // echo 'addPeriodicTimer count: '.$this->singleton->count();
                if ($task->time <= time()) {
                    if ($socketServer->modems[$task->socket->resourceId]->command == null) {
                        $socketServer->modems[$task->socket->resourceId]->command = $task;
                        $task->socket->send(hex2bin($task->data));
                        echo 'send to '.$task->device->name_our.' data: '.$task->data.PHP_EOL;
                    }
                }
            }
        });

        $server->run();
    }
}
