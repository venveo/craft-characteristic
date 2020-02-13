<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        Craft::$app->db->schema->refresh();

        return true;
    }

    protected function createTables()
    {
        $this->createTable('{{%characteristic_groups}}',
            [
                'id' => $this->primaryKey(),
                'structureId' => $this->integer()->notNull(),
                'handle' => $this->string()->notNull(),
                'name' => $this->string()->notNull(),
                'characteristicFieldLayoutId' => $this->integer()->null(),
                'valueFieldLayoutId' => $this->integer()->null(),
                'allowCustomOptionsByDefault' => $this->boolean(),
                'requiredByDefault' => $this->boolean()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );

        $this->createTable('{{%characteristic_characteristics}}', [
            'id' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'handle' => $this->string(),
            'allowCustomOptions' => $this->boolean(),
            'maxValues' => $this->integer()->null(),
            'required' => $this->boolean()->null(),
            'deletedWithGroup' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createTable('{{%characteristic_values}}', [
            'id' => $this->integer()->notNull(),
            'characteristicId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'value' => $this->string()->notNull(),
            'deletedWithCharacteristic' => $this->boolean()->null(),
            'idempotent' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createTable('{{%characteristic_linkblocks}}', [
            'id' => $this->integer()->notNull(),
            'characteristicId' => $this->integer()->notNull(),
            'ownerId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'deletedWithOwner' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%characteristic_groups}}', ['name'], false);
        $this->createIndex(null, '{{%characteristic_groups}}', ['handle'], true);

        $this->createIndex(null, '{{%characteristic_characteristics}}', ['handle'], false);

        $this->createIndex(null, '{{%characteristic_values}}', ['sortOrder'], false);
        $this->createIndex(null, '{{%characteristic_values}}', ['value'], false);
        $this->createIndex(null, '{{%characteristic_values}}', ['value', 'characteristicId'], true);

        $this->createIndex(null, '{{%characteristic_linkblocks}}', ['deletedWithOwner'], false);
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%characteristic_groups}}', ['characteristicFieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_groups}}', ['valueFieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_groups}}', ['structureId'], Table::STRUCTURES, ['id'], 'CASCADE', null);

        $this->addForeignKey(null, '{{%characteristic_characteristics}}', ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_characteristics}}', ['groupId'], '{{%characteristic_groups}}', ['id'], 'CASCADE', null);


        $this->addForeignKey(null, '{{%characteristic_values}}', ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_values}}', ['characteristicId'], '{{%characteristic_characteristics}}', ['id'], 'CASCADE', null);

        $this->addForeignKey(null, '{{%characteristic_linkblocks}}', ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_linkblocks}}', ['ownerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_linkblocks}}', ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_linkblocks}}', ['characteristicId'], '{{%characteristic_characteristics}}', ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%characteristic_linkblocks}}');
        $this->dropTableIfExists('{{%characteristic_values}}');
        $this->dropTableIfExists('{{%characteristic_characteristics}}');
        $this->dropTableIfExists('{{%characteristic_groups}}');
    }
}
