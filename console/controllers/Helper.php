<?php

namespace console\controllers;

use yii;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use console\components\SocketServer;
use Eole\Sandstone;
use common\models\Device;
use common\models\Firmware;
use common\models\DeviceData;

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
                $offset = 0;
                
                for ($i=0;$i<ceil(($a3000->max - $a3000->min)/62);$i++) {

                    if ($len3000 >= 62) {
                        $ll = 62;
                    } else {
                        $ll = $len3000 % 62;
                    }
                    $obj->{'3000'}[$i] = new \stdClass();
                    $obj->{'3000'}[$i]->start = $start3000;
                    $obj->{'3000'}[$i]->length = $ll;
                    $ar = [];
                    for ($y=0;$y<$ll;$y++) {
                        array_push($ar, $start3000 + $y);
                    }
                    $obj->{'3000'}[$i]->addresses = $ar;//array_slice($adr[0], $offset, $ll);
                    $start3000 = $start3000 + 62;
                    $len3000 = $len3000 - 62;
                    $offset = $offset + $ll;
                }

                $obj->{'8000'} = new \stdClass();
                $obj->{'8000'}->start = $a8000->min - 1;
                $obj->{'8000'}->length = $a8000->max - $a8000->min;
                $obj->{'8000'}->addresses = $adr[1];
                
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

        $answer = bin2hex($answer);
        if ($task->command == 'read') {
            if ($answer[2].$answer[3] == '83') {
                echo 'ошибка чтения'.PHP_EOL;
            } else {
                echo 'l: '.(base_convert($answer[4].$answer[5], 16, 10)/2).PHP_EOL;
                $l = base_convert($answer[4].$answer[5], 16, 10)/2;
                $data = substr($answer, 6);

                echo 'l: '.$l.' str: '.strlen($data).PHP_EOL;
                $obj = new \stdClass();
                for ($i=0;$i<$l;$i++) {
                    $obj->{$task->addresses[$i]} = substr($data, $i*4, 4);
                    // echo 'i: '.$i.' address: '.$task->addresses[$i].' value: '.substr($data, $i*4, 4).PHP_EOL;
                }

                Yii::$app->db->open();
                $fw = Firmware::find()->where(['id' => $task->device->firmware_id])->one();
                $fields = json_decode($fw->fields);
                Yii::$app->db->close();
                $dd = new DeviceData($task->device);
                $conn = $dd->getDeviceDb();
                $conn->open();
                $transaction_id = $task->transaction_id.'_'.$task->count;
                $command = $conn->createCommand('select * from '.$dd->table_name.' where transaction_id like "'.$transaction_id.'"');
                $row = $command->query();
                // $dd = $dd->findTransaction($task->transaction_id.'_'.$task->count.PHP_EOL);
                //::find()->where(['transaction_id' => $task->transaction_id.'_'.$task->count])->one();

                if (empty($row)) {
                    echo 'not exist '.$transaction_id.PHP_EOL;
                    $dd = DeviceData($task->device);
                    $dd->transaction_id = $task->transaction_id.'_'.$task->count;
                } else {
                    echo 'exist '.$transaction_id.PHP_EOL;
                }
                // $dd->{'3014'} = 1;
                $dd->save();
                $conn->close();
            }
        }
    }
}
