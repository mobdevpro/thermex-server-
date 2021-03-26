<?php

namespace console\controllers;

use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use console\components\SocketServer;
use Eole\Sandstone;

class ModbusController extends \yii\console\Controller
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

    public function actionStartSocket($port=502)
    {
        $socketServer = new SocketServer();
        $server = IoServer::factory(
            $socketServer,
            $port
        );
 
        $server->loop->addPeriodicTimer(15, function () use (&$server, $socketServer) {        
            foreach ($socketServer->modems as $key => $value) {
                if ($value->socket) {
		            $data = self::BuildReadRequest($value->device->address, 3018, 1);
		            //$data = '01030bbe0001E60A';
                    // $data = '00000000000601030bbf0002';
                    // $data = '000000000006010300060001';

                    // 0103 02 00e4 b80f
                    // 0103 14 00dc 0000 0003 ff06 0000 00e4 001e 0000 0001 0064 a8f2

                    $value->socket->send(hex2bin($data));
                    if ($value->device->id == 1) {
                        if ($value->command == null) {
                            $value->command = 'send';
                        } else {
                            $value->command = null;
                        }
                    }
                    echo 'send to '.$key.PHP_EOL;
                }
            }
        });

        $server->run();
    }
}
