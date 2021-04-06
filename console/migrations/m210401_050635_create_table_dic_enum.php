<?php

use yii\db\Migration;

/**
 * Class m210401_050635_create_table_dic_enum
 */
class m210401_050635_create_table_dic_enum extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('dic_enum', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
            'fields' => $this->text(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210401_050635_create_table_dic_enum cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210401_050635_create_table_dic_enum cannot be reverted.\n";

        return false;
    }
    */
}
