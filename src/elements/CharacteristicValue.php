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
use craft\elements\db\ElementQueryInterface;
use venveo\characteristic\Characteristic as Plugin;
use venveo\characteristic\elements\db\CharacteristicValueQuery;
use venveo\characteristic\records\CharacteristicValue as CharacteristicValueRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicValue extends Element
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $text = '';

    public $characteristicId = null;

    public $sortOrder = 0;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('characteristic', 'Characteristic Values');
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
     */
    public static function find(): ElementQueryInterface
    {
        return new CharacteristicValueQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        return [];
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['characteristicId'], 'number', 'integerOnly' => true];
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
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getCharacteristic()
    {
        if ($this->characteristicId === null) {
            throw new InvalidConfigException('Characteristic value is missing its characteristic ID');
        }

        if (($characteristic = Plugin::$plugin->characteristics->getCharacteristicById($this->characteristicId)) === null) {
            throw new InvalidConfigException('Invalid characteristic ID: ' . $this->characteristicId);
        }

        return $characteristic;
    }


    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return null;
    }

    // Events
    // -------------------------------------------------------------------------
// Events
    // -------------------------------------------------------------------------

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
        $record->text = $this->text;
        $record->sortOrder = $this->sortOrder;

        $record->save(false);

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
        return true;
    }
}
