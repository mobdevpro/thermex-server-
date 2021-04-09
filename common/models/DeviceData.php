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

    var $table_name = NULL;
    var $host = NULL;
    var $dbname = NULL;
    var $username = NULL;
    var $password = NULL;
    var $firmware_id = NULL;

	public function __construct($device){

        $this->table_name = 'device_data_'.$device->id;
        self::$static_table_name = $this->table_name;

        if ($device->db_id != null) {
            $database = Databases::find()->where(['id' => $device->db_id])->one();
            $this->host = $database->address;
            $this->dbname = $database->db_name;
            $this->username = $database->db_login;
            $this->password = $database->db_password;
            $this->firmware_id = $device->firmware_id;
        } else {
            return -1;
        }
	}

    public static function getDb()
    {
        $connection = new \yii\db\Connection([
            'dsn' => 'mysql:host=' . self::$static_host . ';dbname=' . self::$static_dbname,
            'username' => self::$static_username,
            'password' => self::$static_password,
            'charset' => 'utf8',
        ]);
        // $connection->open();
        return $connection;
    }

    public function getDeviceDb()
    {
        $connection = new \yii\db\Connection([
            'dsn' => 'mysql:host=' . $this->host . ';dbname=' . $this->dbname,
            'username' => $this->username,
            'password' => $this->password,
            'charset' => 'utf8',
        ]);
        return $connection;
    }

    public function findTransaction($transaction_id) {
        $command = $connection->createCommand('select * from '.$this->table_name.' where transaction_id='.$transaction_id);
        $row = $command->query();

        if (!empty($row)) {
            return $row;
        } else {
            return null;
        }
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
        $fw = Firmware::find()->where(['id' => self::$firmware_id])->one();
        if (!empty($fw)) {
            $fields = json_decode($fw->fields);
            foreach ($fields as $key => $value) {
                $attributes[] = $key;
            }
        }
        return $attributes;
    }
}