<?php
// ./yii async-worker/daemon modbus
namespace console\components; 

use yii;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use common\models\User;
use common\models\Device;
use console\components\Singleton;
use console\components\SingletonQueue;
use console\components\Simple;
use console\controllers\Helper;

class SocketServer implements MessageComponentInterface
{
    public static $modems;
    // var $singleton;
    var $singletonQueue;
    var $key;
    var $session;

    public function __construct($key)
    {
        // $this->singleton = Singleton::getInstance();
        $this->singletonQueue = SingletonQueue::getInstance();

        $this->singletonQueue->dev = new \stdClass();
        $this->singletonQueue->dev->dev = [];
        $this->singletonQueue->dev->socket = [];
        
        $this->key = $key;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "New connection! ({$conn->resourceId})\n";

        $this->singletonQueue->dev->socket[$conn->resourceId] = new \stdClass();
        $this->singletonQueue->dev->socket[$conn->resourceId]->socket = $conn;
        $this->singletonQueue->dev->socket[$conn->resourceId]->device = null;
        $this->singletonQueue->dev->socket[$conn->resourceId]->command = null;
        $this->singletonQueue->dev->socket[$conn->resourceId]->queue = new \SplPriorityQueue();
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo 'len: '.strlen($msg).PHP_EOL;
        if (strlen($msg) === 0) {
            $from->send('');
            return;
        }

        if ($this->singletonQueue->dev->socket[$from->resourceId]->device == null) {
            echo 'received: '.$msg.PHP_EOL;
            Yii::$app->db->open();
            if (strpos($msg, 'IMEI=')) {
                $ar = explode('IMEI=', $msg);
                $ar = explode(',', $ar[1]);
                $imei = $ar[0];
            } else {
                $imei = $msg;
            }
            $device = Device::find()->where(['imei' => $imei])->one();
            if (!empty($device)) {
                date_default_timezone_set('UTC');
                if ($this->key != 1) {
                    $device->connection_time = date('Y-m-d H:i:s', time());
                }

                $device->is_online = 1;
                $device->save();

                $this->singletonQueue->dev->socket[$from->resourceId]->device = $device;

                if (empty($this->singletonQueue->dev->dev[$device->id])) {
                    $this->singletonQueue->dev->dev[$device->id] = new \stdClass();
                }

                $this->singletonQueue->dev->dev[$device->id]->socket = $from;
                $this->singletonQueue->dev->dev[$device->id]->device = $device;
                $this->singletonQueue->dev->dev[$device->id]->command = null;

                echo 'Законнектился : '.$this->singletonQueue->dev->socket[$from->resourceId]->device->name_our.' onMessage: '.$msg.' bin2hex: '.bin2hex($msg).PHP_EOL;
                
                $address = Helper::getAddresses($device);

                if ($address == null) {
                    echo 'not found address';
                    $from->close();
                    Yii::$app->db->close();
                    return;
                }

                $alarms = Helper::getAlarms($device);

                if ($alarms == null) {
                    echo 'not found alarms';
                    $from->close();
                    Yii::$app->db->close();
                    return;
                }

                $transaction_id = time();
                for ($i=0;$i<count($address->{'3000'});$i++) {
                    // if ($this->key == 1) {
                        $priority = -(time() + 15);
                    // } else {
                    //     $priority = -(time() + 120);
                    // }
                    $data = Helper::BuildReadRequest($device->address, $address->{'3000'}[$i]->start, $address->{'3000'}[$i]->length);
                    $obj = new \stdClass();
                    $obj->device = $device;
                    $obj->socket = $from;
                    $obj->data = $data;
                    $obj->socketId = $from->resourceId;
                    $obj->start = $address->{'3000'}[$i]->start;
                    $obj->length = $address->{'3000'}[$i]->length;
                    $obj->addresses = $address->{'3000'}[$i]->addresses;
                    $obj->time = -$priority;
                    $obj->command = 'read';
                    $obj->transaction_id = $transaction_id;
                    $obj->count = 1;
                    // $this->singleton->insert($obj, $priority);
                    $this->singletonQueue->dev->socket[$from->resourceId]->queue->insert($obj, $priority);
                }

                for ($i=0;$i<count($address->{'8000'});$i++) {
                    // if ($this->key == 1) {
                        $priority = -(time() + 15);
                    // } else {
                    //     $priority = -(time() + 120);
                    // }
                    $data = Helper::BuildReadRequest($device->address, $address->{'8000'}[$i]->start, $address->{'8000'}[$i]->length);
                    $obj = new \stdClass();
                    $obj->device = $device;
                    $obj->socket = $from;
                    $obj->data = $data;
                    $obj->start = $address->{'8000'}[$i]->start;
                    $obj->length = $address->{'8000'}[$i]->length;
                    $obj->addresses = $address->{'8000'}[$i]->addresses;
                    $obj->time = -$priority;
                    $obj->command = 'read';
                    $obj->transaction_id = $transaction_id;
                    $obj->count = 1;
                    // $this->singleton->insert($obj, $priority);
                    $this->singletonQueue->dev->socket[$from->resourceId]->queue->insert($obj, $priority);
                }

                if (count($alarms)) {
                    // if ($this->key == 1) {
                        $priority = -(time() + 15);
                    // } else {
                    //     $priority = -(time() + 120);
                    // }
                    $data = Helper::BuildReadRequest($device->address, $alarms[0] - 1, count($alarms));
                    $obj = new \stdClass();
                    $obj->device = $device;
                    $obj->socket = $from;
                    $obj->data = $data;
                    $obj->socketId = $from->resourceId;
                    $obj->alarms = $alarms;
                    $obj->time = -$priority;
                    $obj->command = 'alarm';
                    $obj->transaction_id = $transaction_id;
                    $obj->count = 1;
                    // $this->singleton->insert($obj, $priority);
                    $this->singletonQueue->dev->socket[$from->resourceId]->queue->insert($obj, $priority);
                    // if ($device->id == 14) {
                    //     $this->singleton->insert($obj, $priority);
                    // }
                }

                echo 'on connect count: '.$this->singletonQueue->dev->socket[$from->resourceId]->queue->count().PHP_EOL;
            } else {
                echo 'not found device'.PHP_EOL;
                $from->close();
            }
            Yii::$app->db->close();
        } else {
            date_default_timezone_set('UTC');
            Yii::$app->db->open();
            $device = Device::find()->where(['id' => $this->singletonQueue->dev->socket[$from->resourceId]->device->id])->one();
            if (!empty($device)) {
                $device->last_active = date('Y-m-d H:i:s', time());
                $device->is_online = 1;
                $device->save();
                $this->singletonQueue->dev->socket[$from->resourceId]->device = $device;
                $this->singletonQueue->dev->dev[$device->id]->device = $device;
            } else {
                $from->close();
            }
            Yii::$app->db->close();
            echo 'device: '.$this->singletonQueue->dev->socket[$from->resourceId]->device->name_our.' onMessage: '.$msg.' bin2hex: '.bin2hex($msg).PHP_EOL;
            $obj = $this->singletonQueue->dev->socket[$from->resourceId]->command;

            $this->singletonQueue->dev->socket[$from->resourceId]->queue->extract();

            $an = Helper::getAnswer($this->singletonQueue->dev->socket[$from->resourceId]->command, $msg);

            if ($obj->command == 'read') {
                $time = time() + 15;
                $obj->time = $time;
                $obj->count = $obj->count + 1;
                $this->singletonQueue->dev->socket[$from->resourceId]->command = null;
                $this->singletonQueue->dev->socket[$from->resourceId]->queue->insert($obj, -$time);
            } else if ($obj->command == 'alarm') {
                $time = time() + 15;
                $obj->time = $time;
                $obj->count = $obj->count + 1;
                $this->singletonQueue->dev->socket[$from->resourceId]->command = null;
                $this->singletonQueue->dev->socket[$from->resourceId]->queue->insert($obj, -$time);
            } else {
                if ($an) {
                    $msg = new \stdClass();
                    $msg->success = true;
                    $msg->message = 'Все отлично!';
                    $msg->device = $this->singletonQueue->dev->socket[$from->resourceId]->command->device->id;
                    $msg->address = $this->singletonQueue->dev->socket[$from->resourceId]->command->address;
                    $msg->value = $this->singletonQueue->dev->socket[$from->resourceId]->command->value;
                    $uu = $this->singletonQueue->dev->socket[$from->resourceId]->command->user;
                    // echo 'user: ';print_r($uu);
                    $userSocket = $this->singletonQueue->user->user[$uu->id]->socket;
                    $userSocket->send(json_encode($msg));
                } else {
                    $msg = new \stdClass();
                    $msg->success = false;
                    $msg->message = 'Ошибка в ответе!';
                    $msg->device = $this->singletonQueue->dev->socket[$from->resourceId]->command->device->id;
                    $msg->address = $this->singletonQueue->dev->socket[$from->resourceId]->command->value;
                    $uu = $this->singletonQueue->dev->socket[$from->resourceId]->command->user;
                    // echo 'user error: ';print_r($uu);
                    $userSocket = $this->singletonQueue->user->user[$uu->id]->socket;
                    $userSocket->send(json_encode($msg));
                }
                $this->singletonQueue->dev->socket[$from->resourceId]->command = null;
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        if ($this->singletonQueue->dev->socket[$conn->resourceId]->device != null) {
            Yii::$app->db->open();
            $device = Device::find()->where(['id' => $this->singletonQueue->dev->socket[$conn->resourceId]->device->id])->one();
            $device->disconnection_time = date('Y-m-d H:i:s', time());
            $device->is_online = 0;
            $device->save();
            Yii::$app->db->close();
        }
        $device = $this->singletonQueue->dev->socket[$conn->resourceId]->device;
        if ($device != null && array_key_exists($device->id, $this->singletonQueue->dev->dev)) {
            unset($this->singletonQueue->dev->dev[$device->id]);
        }
        unset($this->singletonQueue->dev->socket[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $device = $this->singletonQueue->dev->socket[$conn->resourceId]->device;
        if ($device != null && array_key_exists($device->id, $this->singletonQueue->dev->dev)) {
            unset($this->singletonQueue->dev->dev[$device->id]);
        }
        unset($this->singletonQueue->dev->socket[$conn->resourceId]);
        $conn->close();
    }
}