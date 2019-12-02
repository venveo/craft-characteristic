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
 *
 * @property mixed $characteristicValue
 * @property int $id [int(11)]
 * @property int $elementId [int(11)]
 * @property int $characteristicId [int(11)]
 * @property int $valueId [int(11)]
 * @property int $fieldId [int(11)]
 */
class CharacteristicLink extends ActiveRecord
{

    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%characteristic_links}}';
    }

    public function getCharacteristicValue()
    {
        return $this->hasOne(CharacteristicValue::class, ['id' => 'valueId']);
    }

    public function getCharacteristic()
    {
        return $this->hasOne(Characteristic::class, ['id' => 'characteristicId']);
    }
}
