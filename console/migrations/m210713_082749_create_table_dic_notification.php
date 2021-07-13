<?php

use yii\db\Migration;

/**
 * Class m210713_082749_create_table_dic_notification
 */
class m210713_082749_create_table_dic_notification extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('dic_notification', [
            'id' => $this->primaryKey(),
            'label' => $this->string()->notNull()->unique(),
            'address' => $this->string()->notNull()->unique(),
            'description' => $this->string()->notNull(),
            'is_alarm' => $this->integer(1)->notNull(),
            'is_button' => $this->integer(1)->notNull(),
            'data' => $this->text()->notNull(),
        ]);

        $this->createIndex(
            'idx-dic_notification-label',
            'dic_notification',
            'label'
        );

        $this->createIndex(
            'idx-dic_notification-address',
            'dic_notification',
            'address'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210713_082749_create_table_dic_notification cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210713_082749_create_table_dic_notification cannot be reverted.\n";

        return false;
    }
    */
}
