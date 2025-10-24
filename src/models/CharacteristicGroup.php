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

use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\fieldlayoutelements\LightswitchField;
use craft\fieldlayoutelements\TextField;
use craft\fieldlayoutelements\TitleField;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use DateTime;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\records\CharacteristicGroup as CharacteristicGroupRecord;
use yii\base\InvalidConfigException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 *
 * @property FieldLayout $characteristicFieldLayout
 * @property FieldLayout $valueFieldLayout
 */
class CharacteristicGroup extends Model
{
    // Public Properties
    // =========================================================================


    public ?int $id = null;

    public ?string $name = null;

    public ?string $handle = null;

    public bool $allowCustomOptionsByDefault = true;

    public bool $requiredByDefault = false;

    public ?string $uid = null;

    public ?int $characteristicFieldLayoutId = null;

    public ?int $valueFieldLayoutId = null;

    public ?int $structureId = null;

    public ?DateTime $dateCreated = null;

    public ?DateTime $dateUpdated = null;

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
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'valueFieldLayoutId', 'structureId', 'characteristicFieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['allowCustomOptionsByDefault', 'requiredByDefault'], 'boolean'];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => CharacteristicGroupRecord::class];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        return $rules;
    }

    public function getDataForProjectConfig(?string $structureUid = null): array
    {

        $generateLayoutConfig = function (FieldLayout $fieldLayout): array {
            $fieldLayoutConfig = $fieldLayout->getConfig();

            if ($fieldLayoutConfig) {
                if (empty($fieldLayout->id)) {
                    $layoutUid = StringHelper::UUID();
                    $fieldLayout->uid = $layoutUid;
                } else {
                    $layoutUid = Db::uidById('{{%fieldlayouts}}', $fieldLayout->id);
                }

                return [$layoutUid => $fieldLayoutConfig];
            }

            return [];
        };

        $data = [
            'name' => $this->name,
            'handle' => $this->handle,
            'allowCustomOptionsByDefault' => $this->allowCustomOptionsByDefault,
            'requiredByDefault' => $this->requiredByDefault,
            'characteristicFieldLayouts' => $generateLayoutConfig($this->getCharacteristicFieldLayout()),
            'valueFieldLayouts' => $generateLayoutConfig($this->getValueFieldLayout()),
        ];
        if ($structureUid) {
            $data['structure'] = [
                'uid' => $structureUid
            ];
        }

        return $data;
    }

    /**
     * @return FieldLayout
     * @throws InvalidConfigException
     */
    public function getCharacteristicFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('characteristicFieldLayout');
        $fieldLayout = $behavior->getFieldLayout();

        if (!$fieldLayout->getTabs()) {
            $fieldLayout = $this->_createDefaultCharacteristicFieldLayout();
        }

        return $fieldLayout;
    }

    /**
     * @return FieldLayout
     * @throws InvalidConfigException
     */
    public function getValueFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('valueFieldLayout');
        return $behavior->getFieldLayout();
    }

    private function _createDefaultCharacteristicFieldLayout(): FieldLayout
    {
        $fieldLayout = new FieldLayout();
        $fieldLayout->type = Characteristic::class;

        $contentElements = [
            new TitleField([
                'mandatory' => true,
                'label' => Craft::t('app', 'Title'),
            ]),
        ];

        $settingsElements = [
            new TextField([
                'attribute' => 'handle',
                'label' => Craft::t('app', 'Handle'),
                'mandatory' => true,
            ]),
            new LightswitchField([
                'attribute' => 'allowCustomOptions',
                'label' => Craft::t('characteristic', 'Allow custom options'),
            ]),
            new LightswitchField([
                'attribute' => 'required',
                'label' => Craft::t('characteristic', 'Required'),
            ]),
            new TextField([
                'attribute' => 'maxValues',
                'label' => Craft::t('characteristic', 'Maximum values'),
                'type' => 'number',
            ]),
        ];

        $tabs = [];

        $contentTab = new FieldLayoutTab();
        $contentTab->name = Craft::t('app', 'Content');
        $contentTab->setLayout($fieldLayout);
        $contentTab->setElements($contentElements);
        $tabs[] = $contentTab;

        $settingsTab = new FieldLayoutTab();
        $settingsTab->name = Craft::t('app', 'Settings');
        $settingsTab->setLayout($fieldLayout);
        $settingsTab->setElements($settingsElements);
        $tabs[] = $settingsTab;

        $fieldLayout->setTabs($tabs);
        $fieldLayout->setElements(array_merge($contentElements, $settingsElements));

        return $fieldLayout;
    }
}
