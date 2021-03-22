<?php

namespace common\models;

use Yii;
use yii\db\Schema;

/**
 * This is the model class for table "device".
 *
 * @property integer    $id
 * @property string     $status
 * @property string     $serial
 * @property integer    $model_id
 * @property integer    $firmware_id
 * @property string     $imei
 * @property string     $password
 * @property string     $name_our
 * @property string     $date_product
 * @property string     $comment_admin
 * @property integer    $customer_id
 * @property string     $mount_country
 * @property string     $mount_region
 * @property string     $mount_city
 * @property integer    $partner_id
 * @property string     $object_type
 * @property string     $comment_partner
 * @property string     $sim
 * @property string     $timezone
 * @property string     $last_active
 * @property integer    $is_online
 * @property integer    $db_id
 */
class Device extends \yii\db\ActiveRecord
{
    const STATUS_CREATED = 1;           //Создан
    const STATUS_PRODUCED = 2;          //Произведен
    const STATUS_RELEASED = 3;          //Выпущен
    const STATUS_SHIPPED = 4;           //Отгружен
    const STATUS_OPERATED = 5;          //Эксплуатируется
    const STATUS_IN_SERVICE = 6;        //В сервисе
    const STATUS_DECOMMISSIONED = 7;    //Выведен

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        if (array_key_exists('firmware_id', $changedAttributes)) {
            if ($this->db_id == null) {
                $set = Settings::find()->where(['name' => 'devices_per_db'])->one();
                $max = $set->value;
                $dbs = Databases::find()->all();
                $db = null;
                for ($i=0;$i<count($dbs);$i++) {
                    if (count(Device::find()->where(['db_id' => $dbs[$i]->id])->all()) < $max) {
                        $db = $dbs[$i];
                        break;
                    }
                }
            } else {
                $db = Databases::find()->where(['id' => $this->db_id])->one();
            }
            
            if ($db != null) {
                $this->db_id = $db->id;

                $connection = new \yii\db\Connection([
                    'dsn' => 'mysql:host=' . $db->address . ';dbname=' . $db->db_name,
                    'username' => $db->db_login,
                    'password' => $db->db_password,
                    'charset' => 'utf8',
                ]);
                $connection->open();

                if ($connection->schema->getTableSchema('test_table_'.$this->id) == null) {
                    $command = $connection->createCommand('CREATE  TABLE IF NOT EXISTS test_table_'.$this->id.' (
                        `id` int(11) NOT NULL auto_increment,
                        `name` VARCHAR(150) NOT NULL,
                        `device_id` INT(11) not NULL,
                        PRIMARY KEY (`id`))
                        ENGINE = InnoDB;');
                    $command->execute();
                    // echo 'not exist';die;
                } else {
                    // echo 'exist';die;
                }

                $connection->close();
                $this->save();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'device';
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