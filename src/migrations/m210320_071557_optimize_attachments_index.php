<?php

namespace fredyns\attachments\migrations;

use yii\db\Migration;

/**
 * Class m210320_071557_optimize_attachments_index
 */
class m210320_071557_optimize_attachments_index extends Migration
{
    use \fredyns\attachments\ModuleTrait;

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->getModule()->tableName;
        $this->dropIndex("file_model", $table);
        $this->dropIndex("file_item_id", $table);
        $this->createIndex('attachments_model', $table, ['model', 'itemId']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = $this->getModule()->tableName;
        $this->dropIndex("attachments_model", $table);
        $this->createIndex('file_model', $table, ['model']);
        $this->createIndex('file_item_id', $table, ['itemId']);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210320_071557_optimize_attachments_index cannot be reverted.\n";

        return false;
    }
    */
}
