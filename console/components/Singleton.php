<?php
namespace console\components; 

// ini_set('memory_limit', '956M');

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
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
        }
        return self::$instances[$class];
    }

    public static function releaseInstance() {
        $cls = static::class;
        if (isset(self::$instances[$cls])) {
            unset(self::$instances[$cls]);
        }
    }
}