<?php

namespace console\controllers;

use yii;
use React\EventLoop\Factory;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use console\components\SocketServer;
use console\components\Singleton;
use console\components\SingletonQueue;
use console\components\SingletonUser;
use Eole\Sandstone;
use common\models\Device;
use common\models\Firmware;

class Modbus2Controller extends \yii\console\Controller
{
    var $singleton;
    var $singletonQueue;
    var $singletonUser;

    public function actionStartSocket($key=0, $port=502)
    {
        // Singleton::releaseInstance();
        $this->singleton = Singleton::getInstance();
        $this->singletonQueue = SingletonQueue::getInstance();
        $this->singletonUser = SingletonUser::getInstance();

        $loop   = React\EventLoop\Factory::create();
        $pusher = new console\components\Pusher;

        // Listen for the web server to make a ZeroMQ push after an ajax request
        $context = new React\ZMQ\Context($loop);
        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        $pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
        $pull->on('message', array($pusher, 'onBlogEntry'));

        // Set up our WebSocket server for clients wanting real-time updates
        $webSock = new React\Socket\Server('0.0.0.0:8080', $loop); // Binding to 0.0.0.0 means remotes can connect
        $webServer = new Ratchet\Server\IoServer(
            new Ratchet\Http\HttpServer(
                new Ratchet\WebSocket\WsServer(
                    new Ratchet\Wamp\WampServer(
                        $pusher
                    )
                )
            ),
            $webSock
        );

        $loop->run();

        $socketServer = new SocketServer($key);
        $server = IoServer::factory(
            $socketServer,
            $port
        );
 
        $server->loop->addPeriodicTimer(0.1, function () use (&$server, $socketServer) {   
            
            $task = $this->singleton->current();
            if ($task != null) {
                if ($task->time <= time()) {
                    if (array_key_exists($task->socket->resourceId, $this->singletonQueue->dev->socket)) {
                        if ($this->singletonQueue->dev->socket[$task->socket->resourceId]->command == null) {
                            if ($task->command == 'read') {
                                echo 'addPeriodicTimer count: '.$this->singleton->count().PHP_EOL;
                                $this->singletonQueue->dev->socket[$task->socket->resourceId]->command = $task;
                                $task->socket->send(hex2bin($task->data));
                                echo 'send to '.$task->device->name_our.' data: '.$task->data.PHP_EOL;
                            } else {
                                echo 'task write'.PHP_EOL;
                                $this->singleton->extract();
                            }
                        }
                    }
                }
            }
        });

        $server->run();
    }
}
