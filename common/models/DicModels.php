<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "dic_models".
 *
 * @property integer    $id
 * @property string     $name
 */
class DicModels extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dic_models';
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