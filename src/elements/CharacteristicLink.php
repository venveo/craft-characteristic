<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\elements;

use Craft;
use craft\base\BlockElementInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use venveo\characteristic\elements\db\CharacteristicLinkQuery;
use venveo\characteristic\fields\Characteristics;
use venveo\characteristic\fields\Characteristics as CharacteristicsField;
use venveo\characteristic\records\CharacteristicLink as CharacteristicLinkRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 *
 * @property CharacteristicValue $value
 * @property Characteristic $characteristic
 */
class CharacteristicLink extends Element implements BlockElementInterface
{
    // Public Properties
    // =========================================================================

    // Static
    // =========================================================================

    /**
     * @var int|null Field ID
     */
    public $fieldId;
    /**
     * @var int|null Owner ID
     */
    public $ownerId;
    /**
     * @var int|null Characteristic ID
     */
    public $characteristicId;
    /**
     * @var int|null Value ID
     */
    public $valueId;
    /**
     * @var bool
     */
    public $deletedWithOwner;
    /**
     * @var ElementInterface|null The owner element, or false if [[ownerId]] is invalid
     */
    private $_owner;
    private $_value;
    private $_characteristic;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('characteristic', 'Characteristic Link');
    }


    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('characteristic', 'Characteristic Links');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'characteristiclink';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return false;
    }

//    /**
//     * @var bool Whether the block has changed.
//     * @internal
//     * @since 3.4.0
//     */
//    public $dirty = false;

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     * @return CharacteristicLinkQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new CharacteristicLinkQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        if ($handle == 'value') {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = (new Query())
                ->select('valueId as target, id as source')
                ->from('{{%characteristic_links}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return [
                'elementType' => CharacteristicValue::class,
                'map' => $map
            ];
        }

        if ($handle == 'characteristic') {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = (new Query())
                ->select('characteristicId as target, id as source')
                ->from('{{%characteristic_links}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return [
                'elementType' => Characteristic::class,
                'map' => $map
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $names = parent::attributes();
        $names[] = 'owner';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        $names = parent::extraFields();
        $names[] = 'owner';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['fieldId', 'ownerId', 'characteristicId', 'valueId'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getOwner(): ElementInterface
    {
        if ($this->_owner === null) {
            if ($this->ownerId === null) {
                throw new InvalidConfigException('Matrix block is missing its owner ID');
            }

            if (($this->_owner = Craft::$app->getElements()->getElementById($this->ownerId, null, $this->siteId)) === null) {
                throw new InvalidConfigException('Invalid owner ID: ' . $this->ownerId);
            }
        }

        return $this->_owner;
    }

    /**
     * Sets the owner
     *
     * @param ElementInterface|null $owner
     */
    public function setOwner(ElementInterface $owner = null)
    {
        $this->_owner = $owner;
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        if (!$this->propagating) {
            if (!$isNew) {
                $record = CharacteristicLinkRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid Characteristic Link ID: ' . $this->id);
                }
            } else {
                $record = new CharacteristicLinkRecord();
                $record->id = (int)$this->id;
            }

            $record->fieldId = (int)$this->fieldId;
            $record->ownerId = (int)$this->ownerId;
            $record->characteristicId = (int)$this->characteristicId;
            $record->valueId = (int)$this->valueId;
            $record->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        if ($this->hardDelete) {
            return Craft::$app->getDb()->createCommand()
                ->delete('{{%characteristic_links}}', ['id' => $this->id])->execute();
        } else {
            return Craft::$app->getDb()->createCommand()
                ->update('{{%characteristic_links}}', [
                    'deletedWithOwner' => $this->deletedWithOwner,
                ], ['id' => $this->id], [], false)
                ->execute();
        }
        return true;
    }

    public function getValue()
    {
        if ($this->_value === null) {
            if ($this->valueId === null) {
                throw new InvalidConfigException('Characteristic Link is missing its Value ID');
            }

            if (($this->_value = Craft::$app->getElements()->getElementById($this->valueId, CharacteristicValue::class)) === null) {
                throw new InvalidConfigException('Invalid Value ID: ' . $this->valueId);
            }
        }

        return $this->_value;
    }

    public function setValue($element)
    {
        if (is_array($element)) {
            $this->_value = $element[0];
        } else {
            $this->_value = $element;
        }
    }

    public function getCharacteristic()
    {
        if ($this->_characteristic === null) {
            if ($this->characteristicId === null) {
                throw new InvalidConfigException('Characteristic Link is missing its Characteristic ID');
            }

            if (($this->_characteristic = Craft::$app->getElements()->getElementById($this->characteristicId, Characteristic::class)) === null) {
                throw new InvalidConfigException('Invalid Characteristic ID: ' . $this->characteristicId);
            }
        }

        return $this->_characteristic;
    }

    public function setCharacteristic($element)
    {
        if (is_array($element)) {
            return $this->_characteristic = $element[0];
        }

        return $this->_characteristic = $element;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle == 'value') {
            $this->setValue($elements);
        } elseif ($handle == 'characteristic') {
            $this->setCharacteristic($elements);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the Link field.
     *
     * @return Characteristics
     */
    private function _field(): CharacteristicsField
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getFields()->getFieldById($this->fieldId);
    }
}
