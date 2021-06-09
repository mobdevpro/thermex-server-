<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "firmware".
 *
 * @property integer    $id
 * @property string     $name
 * @property string     $fields
 * @property string     $firmware
 * @property string     $fields_a
 * @property string     $alarm
 * @property integer    $author_id
 * @property string     $date
 */
class Firmware extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'firmware';
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
        $attributes[] = 'author';
        return $attributes;
    }
}