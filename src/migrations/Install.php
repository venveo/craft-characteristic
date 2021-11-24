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
use venveo\characteristic\db\Table as CharacteristicTable;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class Install extends Migration
{
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
        $this->createTable(CharacteristicTable::GROUPS,
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

        $this->createTable(CharacteristicTable::CHARACTERISTICS, [
            'id' => $this->integer()->notNull(),
            'groupId' => $this->integer()->notNull(),
            'handle' => $this->string(),
            'allowCustomOptions' => $this->boolean(),
            'maxValues' => $this->integer()->null(),
//            'type' => $this->string()->null(), // bool, text, int, datetime?
            'required' => $this->boolean()->null(),
            'deletedWithGroup' => $this->boolean()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime()->null(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createTable(CharacteristicTable::VALUES, [
            'id' => $this->integer()->notNull(),
            'characteristicId' => $this->integer()->notNull(),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'value' => $this->string()->notNull(),
//            'type' => $this->string()->notNull(),
            'deletedWithCharacteristic' => $this->boolean()->null(),
            'idempotent' => $this->boolean()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
//
//        $this->createTable(CharacteristicTable::VALUESBOOL, [
//            'id' => $this->integer()->notNull(),
//            'value' => $this->boolean()->notNull(),
//            'PRIMARY KEY([[id]])',
//        ]);
//        $this->createTable(CharacteristicTable::VALUESTEXT, [
//            'id' => $this->integer()->notNull(),
//            'value' => $this->text()->notNull(),
//            'PRIMARY KEY([[id]])',
//        ]);
//        $this->createTable(CharacteristicTable::VALUESINT, [
//            'id' => $this->integer()->notNull(),
//            'value' => $this->integer()->notNull(),
//            'PRIMARY KEY([[id]])',
//        ]);
//        $this->createTable(CharacteristicTable::VALUESDATETIME, [
//            'id' => $this->integer()->notNull(),
//            'value' => $this->dateTime()->notNull(),
//            'PRIMARY KEY([[id]])',
//        ]);


        $this->createTable(CharacteristicTable::LINKBLOCKS, [
            'id' => $this->integer()->notNull(),
            'characteristicId' => $this->integer()->notNull(),
            'ownerId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'deletedWithOwner' => $this->boolean()->null(),
            'deletedWithCharacteristic' => $this->boolean()->null(),
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
        $this->createIndex(null, CharacteristicTable::GROUPS, ['name'], false);
        $this->createIndex(null, CharacteristicTable::GROUPS, ['handle'], true);

        $this->createIndex(null, CharacteristicTable::CHARACTERISTICS, ['handle'], false);

        $this->createIndex(null, CharacteristicTable::VALUES, ['sortOrder'], false);
        $this->createIndex(null, CharacteristicTable::VALUES, ['value'], false);
        $this->createIndex(null, CharacteristicTable::VALUES, ['value', 'characteristicId'], true);

        $this->createIndex(null, CharacteristicTable::LINKBLOCKS, ['deletedWithOwner'], false);
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, CharacteristicTable::GROUPS, ['characteristicFieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, CharacteristicTable::GROUPS, ['valueFieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, CharacteristicTable::GROUPS, ['structureId'], Table::STRUCTURES, ['id'], 'CASCADE', null);

        $this->addForeignKey(null, CharacteristicTable::CHARACTERISTICS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, CharacteristicTable::CHARACTERISTICS, ['groupId'], CharacteristicTable::GROUPS, ['id'], 'CASCADE', null);


        $this->addForeignKey(null, CharacteristicTable::VALUES, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, CharacteristicTable::VALUES, ['characteristicId'], CharacteristicTable::CHARACTERISTICS, ['id'], 'CASCADE', null);

//        $this->addForeignKey(null, CharacteristicTable::VALUESBOOL, ['id'], CharacteristicTable::VALUES, ['id'], 'CASCADE', null);
//        $this->addForeignKey(null, CharacteristicTable::VALUESTEXT, ['id'], CharacteristicTable::VALUES, ['id'], 'CASCADE', null);

        $this->addForeignKey(null, CharacteristicTable::LINKBLOCKS, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, CharacteristicTable::LINKBLOCKS, ['ownerId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, CharacteristicTable::LINKBLOCKS, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, CharacteristicTable::LINKBLOCKS, ['characteristicId'], CharacteristicTable::CHARACTERISTICS, ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists(CharacteristicTable::LINKBLOCKS);
        $this->dropTableIfExists(CharacteristicTable::VALUES);
        $this->dropTableIfExists(CharacteristicTable::CHARACTERISTICS);
        $this->dropTableIfExists(CharacteristicTable::GROUPS);
        return true;
    }
}
