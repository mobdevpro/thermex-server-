<?php

use yii\db\Migration;

/**
 * Class m210320_080437_create_table_device
 */
class m210320_080437_create_table_device extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('device', [
            'id' => $this->primaryKey(),
            'status' => $this->string()->notNull(),
            'serial' => $this->string()->notNull()->unique(),
            'model_id' => $this->integer()->notNull(),
            'firmware_id' => $this->integer(),
            'imei' => $this->string()->notNull()->unique(),
            'password' => $this->string(),
            'name_our' => $this->string(),
            'date_product' => $this->date(),
            'comment_admin' => $this->string(),
            'customer_id' => $this->integer(),
            'mount_country' => $this->string(),
            'mount_region' => $this->string(),
            'partner_id' => $this->integer(),
            'object_type' => $this->string(),
            'comment_partner' => $this->string(),
            'sim' => $this->string(),
            'timezone' => $this->string(),
            'last_active' => $this->datetime(),
            'is_online' => $this->integer(1),
            'db_id' => $this->integer(),
        ]);

        $this->createIndex(
            'idx-device-status',
            'device',
            'status'
        );

        $this->createIndex(
            'idx-device-model_id',
            'device',
            'model_id'
        );

        $this->createIndex(
            'idx-device-datasheet_id',
            'device',
            'datasheet_id'
        );

        $this->createIndex(
            'idx-device-customer_id',
            'device',
            'customer_id'
        );

        $this->createIndex(
            'idx-device-mount_country',
            'device',
            'mount_country'
        );

        $this->createIndex(
            'idx-device-mount_region',
            'device',
            'mount_region'
        );

        $this->createIndex(
            'idx-device-partner_id',
            'device',
            'partner_id'
        );
        
        $this->addForeignKey(
            'fk-device-dic_models',
            'device',
            'model_id',
            'dic_models',
            'id',
            'RESTRICT'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210320_080437_create_table_device cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210320_080437_create_table_device cannot be reverted.\n";

        return false;
    }
    */
}
