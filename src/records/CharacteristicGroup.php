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
use craft\records\FieldLayout;
use yii\db\ActiveQueryInterface;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 * @property int $id [int(11)]
 * @property string $handle [varchar(255)]
 * @property string $name [varchar(255)]
 * @property int $characteristicFieldLayoutId [int(11)]
 * @property \yii\db\ActiveQueryInterface $valueFieldLayout
 * @property \yii\db\ActiveQueryInterface $characteristicFieldLayout
 * @property int $valueFieldLayoutId [int(11)]
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
        return '{{%characteristic_groups}}';
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getValueFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'valueFieldLayoutId']);
    }

    /**
     * @return ActiveQueryInterface
     */
    public function getCharacteristicFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'characteristicFieldLayoutId']);
    }

}
