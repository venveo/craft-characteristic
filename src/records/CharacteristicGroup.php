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

use venveo\characteristic\Characteristic;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicGroup extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%characteristic_characteristicgroup}}';
    }
}
