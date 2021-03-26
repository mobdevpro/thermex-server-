<?php
namespace console\components; 

use yii;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\FrameInterface;
use common\models\User;
use common\models\Device;

class SocketServer implements MessageComponentInterface
{
    public $modems;
    
    public function __construct()
    {
        $this->modems = [];
        // $this->enableKeepAlive = false;
    }

    private static function crc16($msg)
    {
        $data = pack('H*',$msg);
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++)
        {
            $crc ^=ord($data[$i]);

            for ($j = 8; $j !=0; $j--)
            {
                if (($crc & 0x0001) !=0)
                {
                    $crc >>= 1;
                    $crc ^= 0xA001;
                }
                else $crc >>= 1;
            }
        }
        return $crc;
    }

    private static function BuildReadRequest($uid, $address, $count) {

        $address = 0xffff & $address;
        $count = 0xffff & $count;
        $uid = 0xff & $uid;

        $command = bin2hex(pack('C', $uid)).'03'.bin2hex(pack('n', $address)).bin2hex(pack('n', $count));
        $crc = self::crc16($command);
        $crc1 = substr(bin2hex(pack('n', (($crc & 0xff00) >> 8))), -2);
        $crc2 = substr(bin2hex(pack('n', (($crc & 0xff)))), -2);
        $command = $command.$crc2.$crc1;
        return $command;
    }

    private static function BuildWriteRequest($uid, $address, $value) {

        $address = 0xffff & $address;
        $value = 0xffff & $value;
        $uid = 0xff & $uid;

        $command = bin2hex(pack('C', $uid)).'10'.bin2hex(pack('n', $address)).'0001'.'02'.bin2hex(pack('n', $value));
        $crc = self::crc16($command);
        $crc1 = substr(bin2hex(pack('n', (($crc & 0xff00) >> 8))), -2);
        $crc2 = substr(bin2hex(pack('n', (($crc & 0xff)))), -2);
        $command = $command.$crc2.$crc1;
        return $command;
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
                $device->last_active = date('Y-m-d H:i:s', time());
                $device->is_online = 1;
                $device->save();
                $this->modems[$from->resourceId]->device = $device;
                echo 'Законектился : '.$this->modems[$from->resourceId]->device->name_our.' onMessage: '.$msg.' bin2hex: '.bin2hex($msg).PHP_EOL;
            } else {
                unset($this->modems[$from->resourceId]);
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

                if ($device->id == 1) {
                    // if ($this->modems[$from->resourceId]->command != null) {
                    //     $data = self::BuildWriteRequest(3005, 315);
                    //     $this->modems[$from->resourceId]->command = null;
                    //     $this->modems[$from->resourceId]->socket->send(hex2bin($data));
                    // } else {

                    // }
                }
            } else {
                unset($this->modems[$from->resourceId]);
                $from->close();
            }
            Yii::$app->db->close();
            echo 'device: '.$this->modems[$from->resourceId]->device->name_our.' onMessage: '.$msg.' bin2hex: '.bin2hex($msg).PHP_EOL;
        }
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