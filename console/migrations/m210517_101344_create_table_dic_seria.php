<?php

use yii\db\Migration;

/**
 * Class m210517_101344_create_table_dic_seria
 */
class m210517_101344_create_table_dic_seria extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('dic_seria', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
        ]);

        $this->addColumn('dic_models', 'seria_id', $this->integer()->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210517_101344_create_table_dic_seria cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210517_101344_create_table_dic_seria cannot be reverted.\n";

        return false;
    }
    */
}
