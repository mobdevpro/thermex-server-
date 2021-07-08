<?php
namespace console\components; 

use yii;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use common\models\User;
use common\models\Device;
use common\models\Firmware;
use console\components\Singleton;
use console\components\SingletonQueue;
use console\components\SocketServer;
use console\controllers\Helper;

class ChatServer implements MessageComponentInterface
{
    public $users;
    var $singleton;
    var $singletonQueue;
    
    public function __construct()
    {
        $this->singleton = Singleton::getInstance();
        $this->singletonQueue = SingletonQueue::getInstance();
        
        $this->singletonQueue->user = new \stdClass();
        $this->singletonQueue->user->user = [];
        $this->singletonQueue->user->socket = [];
    }
   
    public function onOpen(ConnectionInterface $conn)
    {
        echo "Chat New connection! ({$conn->resourceId})\n";
        $this->singletonQueue->user->socket[$conn->resourceId] = new \stdClass();
        $this->singletonQueue->user->socket[$conn->resourceId]->socket = $conn;
        $this->singletonQueue->user->socket[$conn->resourceId]->user = null;
        $this->singletonQueue->user->socket[$conn->resourceId]->command = null;
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (is_null($data)) {
            echo "invalid data\n";
            return $from->close();
        }
        
        $client = User::find()->where(['auth_key' => $data['access_token']])->one();
        if (empty($client)) {
            $msg = new \stdClass();
            $msg->success = false;
            $msg->message = 'Вы не авторизованы!';
            $msg->device = $data['data']['device'];
            $msg->address = $data['data']['address'];
            $from->send(json_encode($msg));
            $from->close();
            echo 'user not found'.PHP_EOL;
            return;
        }

        if (empty($this->singletonQueue->user->user[$client->id])) {
            $this->singletonQueue->user->user[$client->id] = new \stdClass();
            $this->singletonQueue->user->user[$client->id]->socket = $from;
            $this->singletonQueue->user->user[$client->id]->user = $client;
            $this->singletonQueue->user->user[$client->id]->command = $data['data'];
        }

        if($data['command'] == 'setValue') {
            echo 'setValue';
            print_r($msg);

            Yii::$app->db->open();
            $device = Device::find()->where(['id' => $data['data']['device']])->one();

            if (empty($device)) {
                $msg = new \stdClass();
                $msg->success = false;
                $msg->message = 'ТН не найден в базе!';
                $msg->device = $data['data']['device'];
                $msg->address = $data['data']['address'];
                $from->send(json_encode($msg));
                Yii::$app->db->close();
                echo 'device not found'.PHP_EOL;
                return;
            }

            Yii::$app->db->close();

            $fromDev = null;
            // $modems = Yii::$app->session['modems'];

            // echo 'singleton: ';print_r($this->singletonQueue->dev);return;
            if (!array_key_exists($device->id, $this->singletonQueue->dev->dev)) {
                $msg = new \stdClass();
                $msg->success = false;
                $msg->message = 'ТН не найден в базе!';
                $msg->device = $data['data']['device'];
                $msg->address = $data['data']['address'];
                $from->send(json_encode($msg));
                echo 'modems not found'.PHP_EOL;
                return;
            }

            $fromDev = $this->singletonQueue->dev->dev[$device->id];

            if ($fromDev == null) {
                $msg = new \stdClass();
                $msg->success = false;
                $msg->message = 'ТН не найден в базе!';
                $msg->device = $data['data']['device'];
                $msg->address = $data['data']['address'];
                $from->send(json_encode($msg));
                echo 'socket not found'.PHP_EOL;
                return;
            }

            $data2 = Helper::BuildWriteRequest($device, $data['data']['address'], $data['data']['value']);
            if ($data2) {
                $obj = new \stdClass();
                $obj->device = $device;
                $obj->user = $client;
                $obj->socket = $fromDev->socket;
                $obj->data = $data2;
                $obj->socketId = $from->resourceId;
                $obj->address = $data['data']['address'];
                $obj->value = $data['data']['value'];
                $obj->time = time();
                $obj->command = 'write';
                // $obj->transaction_id = $transaction_id;
                // $obj->count = 1;
                $this->singleton->insert($obj, time());
            }

            return;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo "Chat Connection {$conn->resourceId} has disconnected\n";
        $user = $this->singletonQueue->user->socket[$conn->resourceId]->user;
        unset($this->singletonQueue->user->user[$user->id]);
        unset($this->singletonQueue->user->socket[$conn->resourceId]);
        $conn->close();
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Chat An error has occurred: {$e->getMessage()}\n";
        $user = $this->singletonQueue->user->socket[$conn->resourceId]->user;
        unset($this->singletonQueue->user->user[$user->id]);
        unset($this->singletonQueue->user->socket[$conn->resourceId]);
        $conn->close();
    }
}