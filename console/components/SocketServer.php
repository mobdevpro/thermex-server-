<?php
namespace console\components; 

use yii;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\FrameInterface;
use common\models\User;
use common\models\Device;
use console\components\Singleton;
use console\controllers\Helper;

class SocketServer implements MessageComponentInterface
{
    public $modems;
    var $singleton;

    public function __construct()
    {
        $this->singleton = Singleton::getInstance();
        $this->modems = [];
        // $this->enableKeepAlive = false;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "New connection! ({$conn->resourceId})\n";

        $this->modems[$conn->resourceId] = new \stdClass();
        $this->modems[$conn->resourceId]->socket = $conn;
        $this->modems[$conn->resourceId]->device = null;
        $this->modems[$conn->resourceId]->command = null;
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        if ($this->modems[$from->resourceId]->device == null) {
            Yii::$app->db->open();
            $device = Device::find()->where(['imei' => $msg])->one();
            if (!empty($device)) {
                date_default_timezone_set('UTC');
                $device->connection_time = date('Y-m-d H:i:s', time());
                $device->is_online = 1;
                $device->save();
                $this->modems[$from->resourceId]->device = $device;
                echo 'Законнектился : '.$this->modems[$from->resourceId]->device->name_our.' onMessage: '.$msg.' bin2hex: '.bin2hex($msg).PHP_EOL;
                
                $address = Helper::getAddresses($device);

                if ($address == null) {
                    echo 'not found address';
                    // if (array_key_exists($from->resourceId, $this->modems)) {
                    //     unset($this->modems[$from->resourceId]);
                    // }
                    $from->close();
                    Yii::$app->db->close();
                    return;
                }

                $transaction_id = time();
                for ($i=0;$i<count($address->{'3000'});$i++) {
                    $priority = -(time() + 15);
                    $data = Helper::BuildReadRequest($device->address, $address->{'3000'}[$i]->start, $address->{'3000'}[$i]->length);
                    $obj = new \stdClass();
                    $obj->device = $device;
                    $obj->socket = $from;
                    $obj->data = $data;
                    $obj->start = $address->{'3000'}[$i]->start;
                    $obj->length = $address->{'3000'}[$i]->length;
                    $obj->addresses = $address->{'3000'}[$i]->addresses;
                    $obj->time = -$priority;
                    $obj->command = 'read';
                    $obj->transaction_id = $transaction_id;
                    $obj->count = 1;
                    $this->singleton->insert($obj, $priority);
                }

                for ($i=0;$i<count($address->{'8000'});$i++) {
                    $priority = -(time() + 15);
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
                    $this->singleton->insert($obj, $priority);
                }

            } else {
                echo 'not found device'.PHP_EOL;
                // if (array_key_exists($from->resourceId, $this->modems)) {
                //     unset($this->modems[$from->resourceId]);
                // }
                $from->close();
            }
            Yii::$app->db->close();
        } else {
            date_default_timezone_set('UTC');
            Yii::$app->db->open();
            $device = Device::find()->where(['id' => $this->modems[$from->resourceId]->device->id])->one();
            if (!empty($device)) {
                $device->last_active = date('Y-m-d H:i:s', time());
                $device->is_online = 1;
                $device->save();
                $this->modems[$from->resourceId]->device = $device;
            } else {
                // unset($this->modems[$from->resourceId]);
                $from->close();
            }
            Yii::$app->db->close();
            echo 'device: '.$this->modems[$from->resourceId]->device->name_our.' onMessage: '.$msg.' bin2hex: '.bin2hex($msg).PHP_EOL;
            $obj = $this->modems[$from->resourceId]->command;

            $this->singleton->extract();

            Helper::getAnswer($this->modems[$from->resourceId]->command, $msg);

            if ($obj->command == 'read') {
                $time = time() + 15;
                $obj->time = $time;
                $obj->count = $obj->count + 1;
                $this->modems[$from->resourceId]->command = null;
                $this->singleton->insert($obj, -$time);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        Yii::$app->db->open();
        
        $device = Device::find()->where(['id' => $this->modems[$conn->resourceId]->device->id])->one();
        $device->disconnection_time = date('Y-m-d H:i:s', time());
        $device->is_online = 0;
        $device->save();

        Yii::$app->db->close();

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