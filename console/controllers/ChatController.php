<?php

namespace console\controllers;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use console\components\SocketServer;
use Eole\Sandstone;

class ChatController extends \yii\console\Controller
{
    public function actionStartSocket($port=502)
    {
        
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new SocketServer()
                )
            ),
            $port
        );
        $server->run();
    }
}
