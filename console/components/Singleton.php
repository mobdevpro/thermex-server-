<?php
namespace console\components; 

use yii;
use common\models\User;
use common\models\Device;

class Singleton extends \SplPriorityQueue
{
    private static $instances = [];

    protected function __construct() {
        
    }

    protected function __clone() {

    }

    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): Singleton {
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