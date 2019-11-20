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

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    protected function createTables()
    {
        $this->createTable('{{%characteristic_groups}}',
            [
                'id' => $this->primaryKey(),
                'handle' => $this->string()->notNull(),
                'name' => $this->string()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'dateDeleted' => $this->dateTime()->null(),
                'uid' => $this->uid(),
            ]
        );

        $this->createTable('{{%characteristic_characteristics}}', [
            'id' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'handle' => $this->string(),
            'title' => $this->string(),
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
            'text' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createTable('{{%characteristic_links}}', [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'characteristicId' => $this->integer()->notNull(),
            'valueId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'deletedWithElement' => $this->boolean()->defaultValue(false),
            'uid' => $this->uid()
        ]);
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%characteristic_groups}}', ['name'], false);
        $this->createIndex(null, '{{%characteristic_groups}}', ['handle'], false);
        $this->createIndex(null, '{{%characteristic_groups}}', ['dateDeleted'], false);

        $this->createIndex(null, '{{%characteristic_characteristics}}', ['handle'], false);

        $this->createIndex(null, '{{%characteristic_values}}', ['sortOrder'], false);
        $this->createIndex(null, '{{%characteristic_values}}', ['text'], false);

        $this->createIndex(null, '{{%characteristic_links}}', ['deletedWithElement'], false);
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%characteristic_characteristics}}', ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_characteristics}}', ['groupId'], '{{%characteristic_groups}}', ['id'], 'CASCADE', null);


        $this->addForeignKey(null, '{{%characteristic_values}}', ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_values}}', ['characteristicId'], '{{%characteristic_characteristics}}', ['id'], 'CASCADE', null);


        $this->addForeignKey(null, '{{%characteristic_links}}', ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_links}}', ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_links}}', ['valueId'], '{{%characteristic_values}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%characteristic_links}}', ['characteristicId'], '{{%characteristic_characteristics}}', ['id'], 'CASCADE', null);
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%characteristic_links}}');
        $this->dropTableIfExists('{{%characteristic_values}}');
        $this->dropTableIfExists('{{%characteristic_characteristics}}');
        $this->dropTableIfExists('{{%characteristic_groups}}');
    }
}
