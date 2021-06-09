<?php
namespace console\components; 

use yii;
use common\models\User;
use common\models\Device;

class SingletonQueue
{
    private static $instances = [];

    protected function __construct() {
        
    }

    protected function __clone() {

    }

    public function __wakeup() {
        throw new \Exception("Cannot unserialize a SingletonQueue.");
    }

    public static function getInstance(): SingletonQueue {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }

    public static function releaseInstance() {
        $cls = static::class;
        if (isset(self::$instances[$cls])) {
            unset(self::$instances[$cls]);
        }
    }
}