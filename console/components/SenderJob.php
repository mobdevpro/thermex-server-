<?php
namespace console\components; 

use yii;
use yii\base\BaseObject;
use common\models\User;
use common\models\Device;

class SenderJob extends BaseObject implements \yii\queue\JobInterface
{
    public $obj;

    public function execute($queue)
    {
        echo 'start: '.date('Y-m-d H:i:s', time()).' '.$this->obj->device->id.PHP_EOL;
        while (time() - $this->obj->time < 15) {
            sleep(1);
        }
        echo 'end: '.date('Y-m-d H:i:s', time()).' '.$this->obj->device->id.PHP_EOL;
        // $obj->socket-send(hex2bin($obj->data));
        // file_put_contents($this->file, file_get_contents($this->url));
    }
}