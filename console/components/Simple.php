<?php
namespace console\components; 

// ini_set('memory_limit', '956M');

use yii;
use common\models\User;
use common\models\Device;

class Simple
{
    private static $instances = [];
    public $modems;

    protected function __construct() {
        // $this->modems = [];
        // parent::__construct();
    }

    protected function __clone() {

    }

    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): Simple {
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