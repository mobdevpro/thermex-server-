<?php

use yii\db\Migration;

/**
 * Class m210311_110445_add_columns_to_user
 */
class m210311_110445_add_columns_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'fio', $this->string()->defaultValue(null));
        $this->addColumn('{{%user}}', 'avatar', $this->string()->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210311_110445_add_columns_to_user cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210311_110445_add_columns_to_user cannot be reverted.\n";

        return false;
    }
    */
}
