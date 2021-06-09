<?php

namespace common\models;

use Yii;
use yii\base\Model;
/**
 * This is the model class for table "device_data_".
 * @property integer    $id
 * @property string     $time
 * @property string     $transaction_id
 */
// \yii\db\ActiveRecord
class DeviceData extends \yii\db\ActiveRecord
{
    protected static $static_table_name = NULL;
    protected static $static_host = NULL;
    protected static $static_dbname = NULL;
    protected static $static_username = NULL;
    protected static $static_password = NULL;
    protected static $static_firmware_id = NULL;
    protected static $connection = NULL;

	public function __construct(){

	}

    public static function setDevice($device) {
        self::$static_table_name = 'device_data_'.$device->id;

        if ($device->db_id != null) {
            $database = Databases::find()->where(['id' => $device->db_id])->one();
            self::$static_host = $database->address;
            self::$static_dbname = $database->db_name;
            self::$static_username = $database->db_login;
            self::$static_password = $database->db_password;
            self::$static_firmware_id = $device->firmware_id;

            self::$connection = new \yii\db\Connection([
                'dsn' => 'mysql:host=' . self::$static_host . ';dbname=' . self::$static_dbname,
                'username' => self::$static_username,
                'password' => self::$static_password,
                'charset' => 'utf8',
            ]);
        } else {
            return -1;
        }
    }

    public static function getDb()
    {
        return self::$connection;
    }

    public static function setConnection($connection)
    {
        self::$connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return self::$static_table_name;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            
        ];
    }
    
    function attributes()
    {
        $attributes = parent::attributes();
        // $attributes[] = 'time';
        $fw = Firmware::find()->where(['id' => self::$static_firmware_id])->one();
        if (!empty($fw)) {
            $fields = json_decode($fw->fields);
            foreach ($fields as $key => $value) {
                $attributes[] = $key;
            }
        }
        return $attributes;
    }
}