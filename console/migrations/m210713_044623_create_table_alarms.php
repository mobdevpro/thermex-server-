<?php

use yii\db\Migration;

/**
 * Class m210713_044623_create_table_alarms
 */
class m210713_044623_create_table_alarms extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('alarms', [
            'id' => $this->primaryKey(),
            'device_id' => $this->integer()->notNull(),
            'firmware_id' => $this->integer()->notNull(),
            'label' => $this->string()->notNull(),
            'description' => $this->string()->notNull(),
            'is_alarm' => $this->integer(1)->notNull(),
            'address' => $this->string()->notNull(),
            'time' => $this->datetime()->notNull(),
            'is_active' => $this->integer(1)->notNull(),
        ]);

        $this->createIndex(
            'idx-alarms-device_id',
            'alarms',
            'device_id'
        );

        $this->createIndex(
            'idx-alarms-label',
            'alarms',
            'label'
        );

        $this->createIndex(
            'idx-alarms-time',
            'alarms',
            'time'
        );

        $this->createIndex(
            'idx-alarms-firmware_id',
            'alarms',
            'firmware_id'
        );
        
        $this->addForeignKey(
            'fk-alarms-device',
            'alarms',
            'device_id',
            'device',
            'id',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk-alarms-firmware',
            'alarms',
            'firmware_id',
            'firmware',
            'id',
            'RESTRICT'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210713_044623_create_table_alarms cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210713_044623_create_table_alarms cannot be reverted.\n";

        return false;
    }
    */
}
