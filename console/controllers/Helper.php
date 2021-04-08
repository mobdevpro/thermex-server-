<?php

namespace console\controllers;

use yii;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use console\components\SocketServer;
use Eole\Sandstone;
use common\models\Device;
use common\models\Firmware;

class Helper
{

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

    public static function BuildReadRequest($uid, $address, $count) {

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

    public static function BuildWriteRequest($uid, $address, $value) {

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

    private static function getMinMax($array) {
        $min = $array[0];
        $max = $array[count($array) - 1];

        $obj = new \stdClass();
        $obj->min = $min;
        $obj->max = $max;

        return $obj;
    }

    public static function getAddresses($device) {

        if (!empty($device)) {
            Yii::$app->db->open();
            $fw = Firmware::find()->where(['id' => $device->firmware_id])->one();
            if (!empty($fw)) {
                $fields = $fw->fields;
                $fields = json_decode($fields);
                $array = [];
                foreach ($fields as $key => $value) {
                    // echo $key.PHP_EOL;
                    array_push($array, (int)$key);
                }
                
                asort($array);

                $adr = [[], []];
                foreach ($array as $key => $value) {
                    if (strpos($value, '3') === 0) {
                        array_push($adr[0], $value);
                    } else if (strpos($value, '8') === 0) {
                        array_push($adr[1], $value);
                    }
                }

                // print_r($this->getMinMax($adr[0]));
                $a3000 = self::getMinMax($adr[0]);
                $a8000 = self::getMinMax($adr[1]);

                $obj = new \stdClass();
                $obj->{'3000'} = [];

                $len3000 = $a3000->max - $a3000->min;
                $start3000 = $a3000->min - 1;
                
                for ($i=0;$i<ceil(($a3000->max - $a3000->min)/124);$i++) {

                    if ($len3000 >= 124) {
                        $ll = 124;
                    } else {
                        $ll = $len3000 % 124;
                    }
                    $obj->{'3000'}[$i] = new \stdClass();
                    $obj->{'3000'}[$i]->start = $start3000;
                    $obj->{'3000'}[$i]->length = $ll;
                    $start3000 = $start3000 + 124;
                    $len3000 = $len3000 - 124;
                }

                $obj->{'8000'} = new \stdClass();
                $obj->{'8000'}->start = $a8000->min - 1;
                $obj->{'8000'}->length = $a8000->max - $a8000->min;
                
                Yii::$app->db->close();
                return $obj;
            } else {
                Yii::$app->db->close();
                return null;
            }
        } else {
            return null;
        }
    }

    public static function getAnswer($task, $answer) {
        
    }
}
