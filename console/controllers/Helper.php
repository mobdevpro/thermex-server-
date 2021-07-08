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

    public static function BuildWriteRequest($device, $address, $value) {

        if ($device->firmware_id == null) {
            echo 'write: firmware not found'.PHP_EOL;
            return false;
        }

        Yii::$app->db->open();
        $fw = Firmware::find()->where(['id' => $device->firmware_id])->one();
        Yii::$app->db->close();

        if (empty($fw)) {
            echo 'write: firmware not found'.PHP_EOL;
            return false;
        }

        $fields = $fw->fields;
        $fields = json_decode($fields, true);

        echo 'write: '.$address;
        print_r($fields[$address]);
        if (empty($fields[$address])) {
            echo 'write: field not found'.PHP_EOL;
            return false;
        }

        if ($fields[$address]['division'] == 10) {
            if (($fields[$address]['min']*10) > $value || ($fields[$address]['max']*10) < $value) {
                echo 'write: value*10 not in range'.PHP_EOL;
                echo 'value: '.$value.' min: '.($fields[$address]['min']*10).' max: '.($fields[$address]['max']*10).PHP_EOL;
                return false;
            }
        } else {
            if ($fields[$address]['min'] > $value || $fields[$address]['max'] < $value) {
                echo 'write: value not in range'.PHP_EOL;
                echo 'value: '.$value.' min: '.$fields[$address]['min'].' max: '.$fields[$address]['max'].PHP_EOL;
                return false;
            }
        }

        if ($fields[$address]['mode'] != 'RW') {
            echo 'write: address not RW'.PHP_EOL;
            return false;
        }

        $uid = $device->address;
        Firmware::find();
        $address = $address - 1;
        $address = 0xffff & $address;
        $value = 0xffff & $value;
        $uid = 0xff & $uid;

        $command = bin2hex(pack('C', $uid)).'10'.bin2hex(pack('n', $address)).'0001'.'02'.bin2hex(pack('n', $value));
        $crc = self::crc16($command);
        $crc1 = substr(bin2hex(pack('n', (($crc & 0xff00) >> 8))), -2);
        $crc2 = substr(bin2hex(pack('n', (($crc & 0xff)))), -2);
        $command = $command.$crc2.$crc1;

        echo 'write command: '.$command.PHP_EOL;
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

                // $obj = new \stdClass();
                $obj->{'8000'} = [];

                $len8000 = $a8000->max - $a8000->min;
                $start8000 = $a8000->min - 1;
                $offset = 0;
                
                for ($i=0;$i<ceil(($a8000->max - $a8000->min)/62);$i++) {

                    if ($len8000 >= 62) {
                        $ll = 62;
                    } else {
                        $ll = $len8000 % 62;
                    }
                    $obj->{'8000'}[$i] = new \stdClass();
                    $obj->{'8000'}[$i]->start = $start8000;
                    $obj->{'8000'}[$i]->length = $ll;
                    $ar = [];
                    for ($y=0;$y<$ll;$y++) {
                        array_push($ar, $start8000 + $y);
                    }
                    $obj->{'8000'}[$i]->addresses = $ar;//array_slice($adr[0], $offset, $ll);
                    $start8000 = $start8000 + 62;
                    $len8000 = $len8000 - 62;
                    $offset = $offset + $ll;
                }
                
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
                return false;
            } else {
                echo 'getAnswer start: '.date('Y-m-d H:i:s').PHP_EOL;
                $l = base_convert($answer[4].$answer[5], 16, 10)/2;
                $data = substr($answer, 6);

                $obj = new \stdClass();
                for ($i=0;$i<$l;$i++) {
                    $vv = unpack("s", pack("s", hexdec(substr($data, $i*4, 4))));
                    // echo 'ff06 - '.reset($vv).PHP_EOL;
                    $obj->{$task->addresses[$i] + 1} = reset($vv);//base_convert(substr($data, $i*4, 4), 16, 10);
                }

                Yii::$app->db->open();
                $fw = Firmware::find()->where(['id' => $task->device->firmware_id])->one();
                $fields = json_decode($fw->fields, true);
                Yii::$app->db->close();
                DeviceData::setDevice($task->device);
                $conn = DeviceData::getDb();
                $conn->open();
                $transaction_id = $task->transaction_id.'_'.$task->count;
                $dd = DeviceData::find()->where(['transaction_id' => $transaction_id])->one();
                
                if (empty($dd)) {
                    $dd = new DeviceData();
                    $dd->transaction_id = $transaction_id;
                    $dd->time = date('Y-m-d H:i:s', time());
                    echo 'DeviceData empty'.PHP_EOL;
                }
                // print_r($fields);
                foreach ($obj as $key => $value) {
                    if (array_key_exists($key, $fields)) {
                        // echo ' key: '.$key.' division: '.$fields[$key]['division'].' value: '.$value;
                        if ($fields[$key]['division'] === 10) {
                            $value = (float)($value/10);
                        }
                        // echo ' after: '.$value;
                        $dd->{$key} = $value;
                    }
                }

                $dd->save();
                $conn->close();
                DeviceData::setConnection(Yii::$app->db);
                Yii::$app->db->open();
                $dd = DeviceData::find()->one();
                
                if (empty($dd)) {
                    $dd = new DeviceData();
                }
                // print_r($fields);
                foreach ($obj as $key => $value) {
                    if (array_key_exists($key, $fields)) {
                        // echo ' key: '.$key.' division: '.$fields[$key]['division'].' value: '.$value;
                        if ($fields[$key]['division'] === 10) {
                            $value = (float)($value/10);
                        }
                        // echo ' after: '.$value;
                        $dd->{$key} = $value;
                    }
                }
                
                $dd->time = date('Y-m-d H:i:s', time());
                $dd->save();
                Yii::$app->db->close();
                echo 'getAnswer end: '.date('Y-m-d H:i:s').PHP_EOL;
                return true;
            }
        } else {
            if ($answer[2].$answer[3] == '8F' || $answer[2].$answer[3] == '8f') {
                echo 'ошибка записи'.PHP_EOL;
                return false;
            } else {
                echo 'getAnswer write start: '.date('Y-m-d H:i:s').PHP_EOL;
                echo 'write answer: '.$answer.PHP_EOL;
                return true;
            }
        }
    }
}
