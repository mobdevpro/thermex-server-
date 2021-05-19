<?php

use yii\db\Migration;

/**
 * Class m210517_115829_create_table_dictionaries
 */
class m210517_115829_create_table_dictionaries extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('dictionaries', [
            'id' => $this->primaryKey(),
            'file' => $this->string()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210517_115829_create_table_dictionaries cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210517_115829_create_table_dictionaries cannot be reverted.\n";

        return false;
    }
    */
}
