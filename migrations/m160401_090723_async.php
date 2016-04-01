<?php

use yii\db\Migration;

class m160401_090723_async extends Migration
{
    public function up()
    {
        $this->createTable(
            '{{%async}}',
            [
                'id' => 'pk',
                'data' => 'longblob',
                'status' => 'int(1) UNSIGNED DEFAULT 0',
                'queue' => 'varchar(255) NOT NULL'
            ]
        );

        $this->createIndex('queue', '{{%async}}', 'queue');
        $this->createIndex('queue_status', '{{%async}}', ['queue', 'status']);
    }

    public function down()
    {
        $this->dropIndex('queue', '{{%async}}');
        $this->dropIndex('queue_status', '{{%async}}');
        $this->dropTable('{{%async}}');
    }
}
