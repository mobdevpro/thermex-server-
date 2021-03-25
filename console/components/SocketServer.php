<?php
namespace console\components; 

use yii;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\FrameInterface;
use common\models\User;

class SocketServer implements MessageComponentInterface
{
    public $modems;
    
    public function __construct()
    {
        $this->modems = [];
        // $this->enableKeepAlive = false;
    }
   
    public function onOpen(ConnectionInterface $conn)
    {
        echo "New connection! ({$conn->resourceId})\n";

        $this->modems[$conn->resourceId] = new \stdClass();
        $this->modems[$conn->resourceId]->socket = $conn;
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo 'id: '.$from->resourceId.' onMessage: '.$msg.' bin2hex: '.bin2hex($msg).PHP_EOL;
        return;
    }

    public function onClose(ConnectionInterface $conn)
    {
        unset($this->modems[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        unset($this->modems[$conn->resourceId]);
        $conn->close();
    }
}