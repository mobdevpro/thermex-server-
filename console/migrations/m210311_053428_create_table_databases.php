<?php

use yii\db\Migration;

/**
 * Class m210311_053428_create_table_databases
 */
class m210311_053428_create_table_databases extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('databases', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
            'address' => $this->string()->notNull(),
            'db_name' => $this->string()->notNull(),
            'db_login' => $this->string()->notNull(),
            'db_password' => $this->string()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210311_053428_create_table_databases cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210311_053428_create_table_databases cannot be reverted.\n";

        return false;
    }
    */
}
