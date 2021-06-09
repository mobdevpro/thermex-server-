<?php

use yii\db\Migration;

/**
 * Class m210322_055425_create_table_firmware
 */
class m210322_055425_create_table_firmware extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('firmware', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
            'fields' => $this->text(),
            'firmware' => $this->text(),
            'fields_a' => $this->text(),
            'alarm' => $this->text(),
            'author_id' => $this->integer(),
            'date' => $this->date(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210322_055425_create_table_firmware cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210322_055425_create_table_firmware cannot be reverted.\n";

        return false;
    }
    */
}
