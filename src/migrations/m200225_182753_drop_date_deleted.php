<?php

namespace venveo\characteristic\migrations;

use Craft;
use craft\db\Migration;

/**
 * m200225_182753_drop_date_deleted migration.
 */
class m200225_182753_drop_date_deleted extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropColumn('{{%characteristic_characteristics}}', 'dateDeleted');
        $this->addColumn('{{%characteristic_linkblocks}}', 'deletedWithCharacteristic', $this->boolean()->after('deletedWithOwner'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200225_182753_drop_date_deleted cannot be reverted.\n";
        return false;
    }
}
