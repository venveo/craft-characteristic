<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\models;

use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\records\CharacteristicGroup as CharacteristicGroupRecord;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 *
 * @property \craft\models\FieldLayout $characteristicFieldLayout
 * @property \craft\models\FieldLayout $valueFieldLayout
 */
class CharacteristicGroup extends Model
{
    // Public Properties
    // =========================================================================


    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var string|null Group's UID
     */
    public $uid;

    public $characteristicFieldLayoutId;
    public $valueFieldLayoutId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'characteristicFieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Characteristic::class,
                'idAttribute' => 'characteristicFieldLayoutId'
            ],
            'valueFieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => CharacteristicValue::class,
                'idAttribute' => 'valueFieldLayoutId'
            ],
        ];
    }

    /**
     * @return FieldLayout
     */
    public function getCharacteristicFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('characteristicFieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * @return FieldLayout
     */
    public function getValueFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('valueFieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['id', 'valueFieldLayoutId', 'characteristicFieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => CharacteristicGroupRecord::class];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        return $rules;
    }
}
