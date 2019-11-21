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

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 * @property int $id [int(11)]
 * @property int $characteristicId [int(11)]
 * @property int $sortOrder [smallint(6) unsigned]
 * @property string $value [varchar(255)]
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
        return '{{%characteristic_values}}';
    }
}
