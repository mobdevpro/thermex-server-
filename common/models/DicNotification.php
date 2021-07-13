<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dic_notification".
 *
 * @property integer    $id
 * @property string     $label
 * @property string     $description
 * @property integer    $is_alarm
 * @property string     $address
 * @property string     $data
 * @property integer    $is_button
 */
class DicNotification extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dic_notification';
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