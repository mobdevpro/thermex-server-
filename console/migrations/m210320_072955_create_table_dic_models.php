<?php

use yii\db\Migration;

/**
 * Class m210320_072955_create_table_dic_models
 */
class m210320_072955_create_table_dic_models extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('dic_models', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
            'image' => $this->string(),
            'seria' => $this->string(),
            'seria_id' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210320_072955_create_table_dic_models cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210320_072955_create_table_dic_models cannot be reverted.\n";

        return false;
    }
    */
}
