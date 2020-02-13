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
use craft\records\Element;
use craft\records\Field;
use yii\db\ActiveQueryInterface;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 * @property int $id [int(11)]
 * @property int $ownerId [int(11)]
 * @property int $characteristicId [int(11)]
 * @property int $fieldId [int(11)]
 * @property \yii\db\ActiveQueryInterface $element
 * @property mixed $characteristic
 * @property \yii\db\ActiveQueryInterface $field
 * @property \yii\db\ActiveQueryInterface $owner
 * @property bool $deletedWithOwner [tinyint(1)]
 */
class CharacteristicLinkBlock extends ActiveRecord
{

    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%characteristic_linkblocks}}';
    }

    /**
     * Returns the characteristic block’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the characteristic block’s owner.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getOwner(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'ownerId']);
    }

    /**
     * Returns the characteristic block’s field.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getField(): ActiveQueryInterface
    {
        return $this->hasOne(Field::class, ['id' => 'fieldId']);
    }


    /**
     * @return ActiveQueryInterface The relational query object.
     */
    public function getCharacteristic()
    {
        return $this->hasOne(Characteristic::class, ['id' => 'characteristicId']);
    }
}
