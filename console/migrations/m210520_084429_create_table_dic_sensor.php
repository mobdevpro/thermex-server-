<?php

use yii\db\Migration;

/**
 * Class m210520_084429_create_table_dic_sensor
 */
class m210520_084429_create_table_dic_sensor extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('dic_sensor', [
            'id' => $this->primaryKey(),
            'address' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210520_084429_create_table_dic_sensor cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210520_084429_create_table_dic_sensor cannot be reverted.\n";

        return false;
    }
    */
}
