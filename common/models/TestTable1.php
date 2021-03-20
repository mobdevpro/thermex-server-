<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "databases".
 *
 * @property integer    $id
 * @property string     $name
 * @property integer    $device_id
 */
class TestTable1 extends \yii\db\ActiveRecord
{
    protected static $table_name = NULL;
    protected static $host = NULL;
    protected static $dbname = NULL;
    protected static $username = NULL;
    protected static $password = NULL;

	public function __construct($device){

        self::$table_name = 'test_table_'.$device->id;

        if ($device->server_id != null) {
            $database = Databases::find()->where(['id' => $device->server_id])->one();
            self::$host = $database->address;
            self::$dbname = $database->db_name;
            self::$username = $database->db_login;
            self::$password = $database->db_password;
        } else {
            $set = Settings::find()->where(['name' => 'devices_per_db'])->one();
            $max = $set->value;
            $dbs = Databases::find()->all();

            for ($i=0;$i<count($dbs);$i++) {
                if (count(Device::find()->where(['server_id' => $dbs[$i]->id])->all()) < $max) {
                    
                }
            }
        }
	}

    public static function getDb()
    {
        $connection = new \yii\db\Connection([
            'dsn' => 'mysql:host=' . self::$host . ';dbname=' . self::$dbname,
            'username' => self::$username,
            'password' => self::$password,
            'charset' => 'utf8',
        ]);
        $connection->open();
        return $connection;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return self::$table_name;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // [['unit_id'], 'integer'],
//            [['doctor_id', 'digree_id'], 'required'],
        ];
    }
    
    function attributes()
    {
        $attributes = parent::attributes();
        // $attributes[] = 'monitors';
        return $attributes;
    }
    
    // public function getUnit()
    // {
    //     return $this->hasOne(DicUnits::className(), ['id' => 'unit_id']);
    // }
}

// <?php

// namespace common\models;

// use Yii;

// /**
//  * This is the model class for table "databases".
//  *
//  * @property integer    $id
//  * @property string     $name
//  * @property integer    $device_id
//  */
// class Device extends \yii\db\ActiveRecord
// {
//     protected static $table_name = NULL;
//     protected static $host = NULL;
//     protected static $dbname = NULL;
//     protected static $username = NULL;
//     protected static $password = NULL;

// 	public function __construct($database, $device){

// 		self::$table_name = 'test_table_'.$device->id;
//         self::$host = $database->address;
//         self::$dbname = $database->db_name;
//         self::$username = $database->db_login;
//         self::$password = $database->db_password;
// 		// parent::__construct();
// 	}

//     public static function getDb()
//     {
//         $connection = new \yii\db\Connection([
//             'dsn' => 'mysql:host=' . self::$host . ';dbname=' . self::$dbname,
//             'username' => self::$username,
//             'password' => self::$password,
//             'charset' => 'utf8',
//         ]);
//         $connection->open();
//         return $connection;
//     }

//     /**
//      * @inheritdoc
//      */
//     public static function tableName()
//     {
//         return self::$table_name;
//     }

//     /**
//      * @inheritdoc
//      */
//     public function rules()
//     {
//         return [
//             // [['unit_id'], 'integer'],
// //            [['doctor_id', 'digree_id'], 'required'],
//         ];
//     }
    
//     function attributes()
//     {
//         $attributes = parent::attributes();
//         // $attributes[] = 'monitors';
//         return $attributes;
//     }
    
//     // public function getUnit()
//     // {
//     //     return $this->hasOne(DicUnits::className(), ['id' => 'unit_id']);
//     // }
// }