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
use craft\db\Table;
use craft\db\Table as DbTable;
use craft\elements\db\ElementQueryInterface;
use venveo\characteristic\elements\db\CharacteristicLinkBlockQuery;
use venveo\characteristic\fields\Characteristics;
use venveo\characteristic\fields\Characteristics as CharacteristicsField;
use venveo\characteristic\records\CharacteristicLinkBlock as CharacteristicLinkBlockRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 *
 * @property CharacteristicValue $value
 * @property null|ElementInterface $owner
 * @property Characteristic $characteristic
 */
class CharacteristicLinkBlock extends Element implements BlockElementInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('characteristic', 'Characteristic Link Block');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('characteristic', 'Characteristic Link Block');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('characteristic', 'Characteristic Link Blocks');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('characteristic', 'Characteristic Link Blocks');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'characteristiclinkblock';
    }

    /**
     * @inheritdoc
     * @return CharacteristicLinkBlockQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new CharacteristicLinkBlockQuery(static::class);
    }

    public static function isLocalized(): bool
    {
        return true;
    }

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
     * @var bool Whether the block has changed.
     * @internal
     * @since 3.4.0
     */
    public $dirty = false;

    /**
     * @var bool Whether the block was deleted along with its owner
     * @see beforeDelete()
     */
    public $deletedWithOwner = false;

    public $deletedWithCharacteristic = false;

    /**
     * @var ElementInterface|null The owner element, or false if [[ownerId]] is invalid
     */
    private $_owner;

    /**
     * @var ElementInterface|null The characteristic element, or false if [[characteristicId]] is invalid
     */
    private $_characteristic;

    private $_values;

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $names = parent::attributes();
        $names[] = 'owner';
        $names[] = 'characteristic';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        $names = parent::extraFields();
        $names[] = 'owner';
        $names[] = 'characteristic';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['fieldId', 'ownerId', 'characteristicId'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        try {
            $owner = $this->getOwner();
        } catch (InvalidConfigException $e) {
            $owner = $this->duplicateOf;
        }

        if (!$owner) {
            return [Craft::$app->getSites()->getPrimarySite()->id];
        }

        return [$owner->siteId];
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getOwner(): ElementInterface
    {
        if ($this->_owner === null) {
            if ($this->ownerId === null) {
                throw new InvalidConfigException('Characteristic Link Block is missing its owner ID');
            }

            if (($this->_owner = Craft::$app->getElements()->getElementById($this->ownerId, null, $this->siteId)) === null) {
                throw new InvalidConfigException('Invalid owner ID: ' . $this->ownerId);
            }
        }

        return $this->_owner;
    }

    /**
     * @throws InvalidConfigException
     * @return Characteristic
     */
    public function getCharacteristic(): Characteristic
    {
        if ($this->_characteristic === null) {
            if ($this->characteristicId === null) {
                throw new InvalidConfigException('Characteristic Link Block is missing its characteristic ID');
            }

            if (($this->_characteristic = Craft::$app->getElements()->getElementById($this->characteristicId, Characteristic::class, $this->siteId)) === null) {
                throw new InvalidConfigException('Invalid characteristic ID: ' . $this->characteristicId);
            }
        }

        return $this->_characteristic;
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
     * Sets the characteristic
     *
     * @param ElementInterface|null $characteristic
     */
    public function setCharacteristic(ElementInterface $characteristic = null)
    {
        $this->_characteristic = $characteristic;
    }

    // Events
    // -------------------------------------------------------------------------


    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        if (!$this->propagating) {
            if (!$isNew) {
                $record = CharacteristicLinkBlockRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid Characteristic Link Block ID: ' . $this->id);
                }
            } else {
                $record = new CharacteristicLinkBlockRecord();
                $record->id = (int)$this->id;
            }

            $record->fieldId = (int)$this->fieldId;
            $record->ownerId = (int)$this->ownerId;
            $record->characteristicId = (int)$this->characteristicId;

            $valueElements = [];
            if (is_iterable($this->_values)) {
                $valueService = \venveo\characteristic\Characteristic::getInstance()->characteristicValues;
                $targetIds = [];

                $conditions = [
                    'and',
                    [
                        'fieldId' => $this->fieldId,
                        'sourceId' => $this->id,
                        'sourceSiteId' => $this->siteId
                    ]
                ];
                // Delete the relations from this block to its values
                Craft::$app->getDb()->createCommand()
                    ->delete(Table::RELATIONS, $conditions)
                    ->execute();

                // Delete the relations from owner element to the characteristic & all of its potential values
                $allValueIds = CharacteristicValue::find()->characteristicId($this->characteristicId)->ids();
                $allValueIds[] = $this->characteristicId;
                $conditions = [
                    'or'
                ];
                foreach($allValueIds as $valueId) {
                    $conditions[] = [
                        'fieldId' => $this->fieldId,
                        'sourceId' => $this->ownerId,
                        'targetId' => $valueId,
                        'sourceSiteId' => $this->siteId
                    ];
                }
                Craft::$app->getDb()->createCommand()
                    ->delete(Table::RELATIONS, $conditions)->execute();

                // Collect all of the values we need to create relations for
                foreach ($this->_values as $value) {
                    $valueId = $value;
                    if ($value instanceof CharacteristicValue && isset($value->id)) {
                        $valueId = $value->id;
                        $valueElement = $value;
                    } else {
                        $valueElement = $valueService->getCharacteristicValueById($valueId);
                    }

                    if (!$valueElement) {
                        throw new Exception('Invalid characteristic value ID: ', $valueId);
                    }

                    $targetIds[] = $valueElement->id;
                    $valueElements[] = $valueElement;
                }

                $batchInsertRelationValues = [];
                foreach ($targetIds as $sortOrder => $targetId) {
                    // Create relationship from linkblock to value
                    $batchInsertRelationValues[] = [
                        $this->fieldId,
                        $this->id, // LinkBlock
                        $this->siteId,
                        $targetId, // Value
                        null
                    ];

                    // Create relation from owner element to value
                    $batchInsertRelationValues[] = [
                        $this->fieldId,
                        $this->ownerId, // owner element
                        $this->siteId,
                        $targetId,
                        null
                    ];
                }

                // Create a relationship from owner element to characteristic
                $batchInsertRelationValues[] = [
                    $this->fieldId,
                    $this->ownerId,
                    $this->siteId,
                    $this->characteristicId,
                    null
                ];

                $columns = [
                    'fieldId',
                    'sourceId',
                    'sourceSiteId',
                    'targetId',
                    'sortOrder'
                ];
                Craft::$app->getDb()->createCommand()
                    ->batchInsert(Table::RELATIONS, $columns, $batchInsertRelationValues)
                    ->execute();

                $this->setValues($valueElements);
            }
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

        // Update the block record
        Craft::$app->getDb()->createCommand()
            ->update('{{%characteristic_linkblocks}}', [
                'deletedWithOwner' => $this->deletedWithOwner,
                'deletedWithCharacteristic' => $this->deletedWithCharacteristic
            ], ['id' => $this->id], [], false)
            ->execute();

        return true;
    }

    public function setValues($values)
    {
        $this->_values = $values;
    }

    public function getValues()
    {
        if ($this->_values) {
            return $this->_values;
        }
        $query = CharacteristicValue::find();
        $query->relatedTo([
            'sourceElement' => $this->id,
            'fieldId' => $this->fieldId
        ]);
        return $query;
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        if ($handle === 'values') {
            // Get the source element IDs
            $sourceElementIdsBySiteId = [];

            foreach ($sourceElements as $element) {
                $sourceElementIdsBySiteId[$element->siteId][] = $element->id;
            }

            $condition = ['and'];


            foreach ($sourceElementIdsBySiteId as $siteId => $elementIds) {
                $condition[] = [
                    'and',
                    ['sourceId' => $elementIds],
                    ['or', ['sourceSiteId' => $siteId], ['sourceSiteId' => null]],
                ];
            }

            // Return any relation data on these elements, defined with this field
            $map = (new Query())
                ->select(['sourceId as source', 'targetId as target'])
                ->from([DbTable::RELATIONS])
                ->where($condition)
                ->orderBy(['sortOrder' => SORT_ASC])
                ->all();

            $criteria = [];

            return [
                'elementType' => CharacteristicValue::class,
                'map' => $map,
                'criteria' => $criteria,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle === 'values') {
            $this->setValues($elements);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

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
