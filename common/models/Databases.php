<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "databases".
 *
 * @property integer    $id
 * @property string     $name
 * @property string     $address
 * @property string     $db_name
 * @property string     $db_login
 * @property string     $db_password
 */
class Databases extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'databases';
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