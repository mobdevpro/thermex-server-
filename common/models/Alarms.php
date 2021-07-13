<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "alarms".
 *
 * @property integer    $id
 * @property integer    $device_id
 * @property integer    $firmware_id
 * @property string     $label
 * @property string     $description
 * @property integer    $is_alarm
 * @property string     $address
 * @property string     $time
 * @property integer    $is_active
 */
class Alarms extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'alarms';
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
}