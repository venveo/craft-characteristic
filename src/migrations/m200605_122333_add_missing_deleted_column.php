<?php

namespace venveo\characteristic\migrations;

use Craft;
use craft\db\Migration;

/**
 * m200605_122333_add_missing_deleted_column migration.
 */
class m200605_122333_add_missing_deleted_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%characteristic_characteristics}}','dateDeleted', $this->datetime()->after('dateUpdated')->defaultValue(null));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200605_122333_add_missing_deleted_column cannot be reverted.\n";
        return false;
    }
}
