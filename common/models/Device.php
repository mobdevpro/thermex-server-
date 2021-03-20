<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "device".
 *
 * @property integer    $id
 * @property string     $status
 * @property string     $serial
 * @property integer    $model_id
 * @property integer    $datasheet_id
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