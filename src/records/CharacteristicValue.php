<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\records;

use craft\db\ActiveRecord;
use venveo\characteristic\db\Table;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 * @property int $id [int(11)]
 * @property int $characteristicId [int(11)]
 * @property int $sortOrder [smallint(6) unsigned]
 * @property string $value [varchar(255)]
 * @property bool $deletedWithCharacteristic [tinyint(1)]
 * @property bool $idempotent [tinyint(1)]
 */
class CharacteristicValue extends ActiveRecord
{

    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return Table::VALUES;
    }
}
