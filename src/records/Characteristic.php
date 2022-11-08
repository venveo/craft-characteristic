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
use craft\db\SoftDeleteTrait;
use venveo\characteristic\db\Table;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 * @property int $id [int(11)]
 * @property int $groupId [int(11)]
 * @property string $handle [varchar(255)]
 * @property bool $deletedWithGroup [tinyint(1)]
 * @property bool $allowCustomOptions [tinyint(1)]
 * @property bool $required [tinyint(1)]
 * @property int $maxValues [int(11)]
 */
class Characteristic extends ActiveRecord
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait;

    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return Table::CHARACTERISTICS;
    }
}
