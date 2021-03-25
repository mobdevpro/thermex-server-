<?php

namespace console\controllers;

use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use console\components\SocketServer;
use Eole\Sandstone;

class ModbusController extends \yii\console\Controller
{
    public function actionStartSocket($port=502)
    {
        $socketServer = new SocketServer();
        $server = IoServer::factory(
            $socketServer,
            $port
        );

        $server->loop->addPeriodicTimer(15, function () use (&$server, $socketServer) {        
            foreach ($socketServer->modems as $key => $value) {
                if ($value->socket) {
                    $data = '01030bbe0001E60A';
                    // $data = '00000000000601030bbf0002';
                    // $data = '000000000006010300060001';
                    $value->socket->send(hex2bin($data));
                    echo 'send to '.$key.PHP_EOL;
                }
            }
        });

        $server->run();
    }
}
