<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "db_locality".
 *
 * @property integer    $id
 * @property integer    $erased
 * @property integer    $owner
 * @property integer    $public
 * @property integer    $order
 * @property integer    $change_to
 * @property integer    $part_of
 * @property integer    $level
 * @property string     $type
 * @property string     $name
 * @property string     $name_clarification
 * @property string     $name_en
 * @property string     $wiki
 * @property string     $timezone
 * @property string     $alpha2
 * @property string     $code
 * @property string     $osm_id
 * @property string     $fias
 * @property string     $fias_type
 * @property string     $okato
 * @property string     $oktmo
 * @property integer    $peoples
 * @property float      $point_lat
 * @property float      $point_lon
 * @property float      $range_lower_lat
 * @property float      $range_lower_lon
 * @property float      $range_upper_lat
 * @property float      $range_upper_lon
 */
class Locations extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'db_locality';
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