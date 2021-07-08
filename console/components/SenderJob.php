<?php
namespace console\components; 

use yii;
use Ratchet\ConnectionInterface;
use yii\base\BaseObject;
use common\models\User;
use common\models\Device;
use bazilio\async\models\AsyncTask;
use console\components\SocketServer;

class SenderJob extends AsyncTask
{
    public $obj;
    public static $queueName = 'modbus';
    var $session;

    public function execute()
    {
        echo 'start: '.date('Y-m-d H:i:s', time()).' '.$this->obj->device->id.PHP_EOL;
        while (time() < $this->obj->time) {
            sleep(1);
        }
        // echo 'count - '.count(SocketServer::getModems()->dev->socket);
        // $socket = new ConnectionInterface();
        $socket = $this->obj->socket;
        $socket->send(hex2bin($this->obj->data));
        echo 'end: '.date('Y-m-d H:i:s', time()).' '.$this->obj->device->id.PHP_EOL;
        // file_put_contents($this->file, file_get_contents($this->url));
    }
}