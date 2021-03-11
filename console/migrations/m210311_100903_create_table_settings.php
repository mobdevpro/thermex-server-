<?php

use yii\db\Migration;

/**
 * Class m210311_100903_create_table_settings
 */
class m210311_100903_create_table_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('settings', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
            'description' => $this->string()->notNull(),
            'value' => $this->string()->notNull(),
            'type' => $this->string()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210311_100903_create_table_settings cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210311_100903_create_table_settings cannot be reverted.\n";

        return false;
    }
    */
}
