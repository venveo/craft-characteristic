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
use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\validators\UniqueValidator;
use venveo\characteristic\Characteristic as Plugin;
use venveo\characteristic\elements\db\CharacteristicValueQuery;
use venveo\characteristic\helpers\DrilldownState;
use venveo\characteristic\records\CharacteristicValue as CharacteristicValueRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 * @property Characteristic|null $characteristic
 */
class CharacteristicValue extends Element
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $value = '';

    public $characteristicId = null;

    public $sortOrder = 0;

    public $idempotent = false;

    public $deletedWithCharacteristic = false;

    // Static Methods
    // =========================================================================
    /**
     * @var Characteristic
     */
    private $_characteristic;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('characteristic', 'Characteristic Value');
    }


    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('characteristic', 'Characteristic Values');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'characteristicValue';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
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

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     * @return CharacteristicValueQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new CharacteristicValueQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array
    {
        if ($handle == 'characteristic') {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = (new Query())
                ->select('id as source, characteristicId as target')
                ->from('{{%characteristic_characteristics}}')
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
    protected static function defineSources(string $context = null): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['characteristicId'], 'number', 'integerOnly' => true];

        $rules[] = [
            ['value'],
            UniqueValidator::class,
            'targetAttribute' => ['value', 'characteristicId'],
            'targetClass' => CharacteristicValueRecord::class,
            'message' => Craft::t('characteristic', 'Characteristic Value must be unique to this characteristic')
        ];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        return parent::getFieldLayout() ?? $this->getCharacteristic()->getGroup()->getValueFieldLayout();
    }

    /**
     * @return Characteristic|null
     * @throws InvalidConfigException
     */
    public function getCharacteristic()
    {
        if ($this->_characteristic !== null) {
            return $this->_characteristic;
        }

        if ($this->characteristicId === null) {
            throw new InvalidConfigException('Characteristic value is missing its characteristic ID');
        }

        if (($characteristic = Plugin::$plugin->characteristics->getCharacteristicById($this->characteristicId)) === null) {
            throw new InvalidConfigException('Invalid characteristic ID: ' . $this->characteristicId);
        }

        return $this->_characteristic = $characteristic;
    }

    public function setCharacteristic(Characteristic $characteristic)
    {

        if ($characteristic->id) {
            $this->characteristicId = $characteristic->id;
        }

        $this->_characteristic = $characteristic;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle == 'characteristic') {
            $characteristic = $elements[0] ?? null;
            $this->setCharacteristic($characteristic);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    public function applyToDrilldownState(DrilldownState $state)
    {
        $newState = clone $state;
        if ($this->idempotent) {
            return $newState->setCharacteristicSatisfied($this->getCharacteristic());
        }

        return $newState->setCharacteristicValue($this);
    }


    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('characteristics/' . $this->getCharacteristic()->getGroup()->handle . '/' . $this->getCharacteristic()->id . '/' . $this->id);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        // Get the user record
        if (!$isNew) {
            $record = CharacteristicValueRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid characteristic value ID: ' . $this->id);
            }
        } else {
            $record = new CharacteristicValueRecord();
            $record->id = (int)$this->id;
        }

        $record->characteristicId = $this->characteristicId;
        $record->value = $this->value;
        $record->sortOrder = $this->sortOrder;
        $record->idempotent = $this->idempotent;

        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        $group = $this->getCharacteristic()->getGroup();

        $this->fieldLayoutId = $group->valueFieldLayoutId;

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        if ($this->deletedWithCharacteristic) {
            Craft::$app->getDb()->createCommand()
                ->update('{{%characteristic_values}}', [
                    'deletedWithCharacteristic' => $this->deletedWithCharacteristic
                ], ['id' => $this->id], [], false)
                ->execute();
        }

        return true;
    }

    public function getEditorHtml(): string
    {
        return Craft::$app->view->renderTemplate('characteristic/characteristics/_partials/_edit-value-fields', [
            'value' => $this
        ]);
    }
}
