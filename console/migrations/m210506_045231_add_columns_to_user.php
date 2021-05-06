<?php

use yii\db\Migration;

/**
 * Class m210506_045231_add_columns_to_user
 */
class m210506_045231_add_columns_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'inn', $this->string()->defaultValue(null));
        $this->addColumn('{{%user}}', 'workphone', $this->string()->defaultValue(null));
        $this->addColumn('{{%user}}', 'staff', $this->string()->defaultValue(null));
        $this->addColumn('{{%user}}', 'phone', $this->string()->defaultValue(null));
        $this->addColumn('{{%user}}', 'partner_contact', $this->string(1000)->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210506_045231_add_columns_to_user cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210506_045231_add_columns_to_user cannot be reverted.\n";

        return false;
    }
    */
}
