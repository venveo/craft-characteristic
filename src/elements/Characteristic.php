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
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use venveo\characteristic\Characteristic as Plugin;
use venveo\characteristic\elements\db\CharacteristicQuery;
use venveo\characteristic\records\Characteristic as CharacteristicRecord;
use venveo\characteristic\records\CharacteristicLink;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 * @property CharacteristicValue[] $values
 * @property mixed $group
 */
class Characteristic extends Element
{
    // Public Properties
    // =========================================================================
    public $handle = '';

    public $groupId = null;

    /**
     * @var int|null New parent ID
     */
    public $newParentId;

    /**
     * @var bool
     */
    public $allowCustomOptions = true;

    /**
     * @var bool
     */
    public $required = false;

    public $maxValues = 1;

    /**
     * @var bool|null
     * @see _hasNewParent()
     */
    private $_hasNewParent;

    // Static Methods
    // =========================================================================
    /**
     * @var ElementInterface[]|string|null
     */
    private $_values;

    private $_elementValues = [];

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('characteristic', 'Characteristic');
    }


    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('characteristic', 'Characteristics');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'characteristic';
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
        return true;
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
    public static function eagerLoadingMap(array $sourceElements, string $handle): array
    {
        if ($handle == 'values') {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = (new Query())
                ->select('id as target, characteristicId as source')
                ->from('{{%characteristic_values}}')
                ->where(['in', 'characteristicId', $sourceElementIds])
                ->orderBy('sortOrder')
                ->all();

            return [
                'elementType' => CharacteristicValue::class,
                'map' => $map
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [];
        $groups = Plugin::$plugin->characteristicGroups->getEditableGroups();
        foreach ($groups as $group) {
            $sources[] = [
                'key' => 'group:' . $group->uid,
                'label' => Craft::t('characteristic', $group->name),
                'data' => ['handle' => $group->handle],
                'criteria' => [
                    'groupId' => $group->id
                ],
                'structureId' => $group->structureId,
                'structureEditable' => Craft::$app->getRequest()->getIsConsoleRequest() ? true : Craft::$app->getUser()->checkPermission('editCharacteristics:' . $group->uid),
            ];
        }
        return $sources;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'title' => ['label' => Craft::t('app', 'Title')],
            'handle' => ['label' => Craft::t('app', 'Handle')],
            'required' => ['label' => Craft::t('characteristic', 'Required')],
            'allowCustomOptions' => ['label' => Craft::t('characteristic', 'Allow Custom Options')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'title',
            'required',
            'allowCustomOptions',
            'dateCreated',
            'dateUpdated',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['groupId', 'maxValues'], 'number', 'integerOnly' => true];
        $rules[] = [['required', 'allowCustomOptions'], 'boolean'];
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
        return parent::getFieldLayout() ?? $this->getGroup()->getCharacteristicFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getGroup()
    {
        if ($this->groupId === null) {
            throw new InvalidConfigException('Group is missing its group ID');
        }

        if (($group = Plugin::$plugin->characteristicGroups->getGroupById($this->groupId)) === null) {
            throw new InvalidConfigException('Invalid characteristic group ID: ' . $this->groupId);
        }

        return $group;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle == 'values') {
            $this->setValues($elements);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        $group = $this->getGroup();

        $url = UrlHelper::cpUrl('characteristics/' . $group->handle . '/' . $this->id);

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Title'),
                'siteId' => $this->siteId,
                'id' => 'title',
                'name' => 'title',
                'value' => $this->title,
                'errors' => $this->getErrors('title'),
                'first' => true,
                'autofocus' => true,
                'required' => true
            ]
        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }

    /**
     * @param string $type
     * @return ElementQueryInterface
     */
    public function getRelatedElements($type = Entry::class)
    {
        $linkQuery = CharacteristicLink::find();
        $linkQuery->where(['characteristicId' => $this->id]);
        // TODO: Should this be ->column?
        $ids = $linkQuery->select('ownerId')->indexBy('ownerId')->all();
        $criteria['id'] = array_keys($ids);
        /** @var ElementQueryInterface $query */
        $query = Craft::configure($type::find(), $criteria);
        return $query;
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        // Get the user record
        if (!$isNew) {
            $record = CharacteristicRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid characteristic ID: ' . $this->id);
            }
        } else {
            $record = new CharacteristicRecord();
            $record->id = (int)$this->id;
        }

        $record->groupId = $this->groupId;
        $record->handle = $this->handle;
        $record->required = $this->required;

        if (!$this->maxValues) {
            $this->maxValues = null;
        }

        $record->maxValues = $this->maxValues;
        $record->allowCustomOptions = $this->allowCustomOptions;

        $record->save(false);

        // Has the parent changed?
        if ($this->_hasNewParent()) {
            if (!$this->newParentId) {
                Craft::$app->getStructures()->appendToRoot($this->structureId, $this);
            } else {
                Craft::$app->getStructures()->append($this->structureId, $this, $this->getParent());
            }
        }

        parent::afterSave($isNew);
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * Returns whether the category has been assigned a new parent entry.
     *
     * @return bool
     * @see beforeSave()
     * @see afterSave()
     */
    private function _hasNewParent(): bool
    {
        if ($this->_hasNewParent !== null) {
            return $this->_hasNewParent;
        }

        return $this->_hasNewParent = $this->_checkForNewParent();
    }

    /**
     * Checks if an category was submitted with a new parent category selected.
     *
     * @return bool
     */
    private function _checkForNewParent(): bool
    {
        // Is it a brand new category?
        if ($this->id === null) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if ($this->newParentId === null) {
            return false;
        }

        // Is it set to the top level now, but it hadn't been before?
        if (!$this->newParentId && $this->level != 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($this->newParentId && $this->level == 1) {
            return true;
        }

        // Is the newParentId set to a different category ID than its previous parent?
        $oldParentQuery = self::find();
        $oldParentQuery->ancestorOf($this);
        $oldParentQuery->ancestorDist(1);
        $oldParentQuery->siteId($this->siteId);
        $oldParentQuery->anyStatus();
        $oldParentQuery->select('elements.id');
        $oldParentId = $oldParentQuery->scalar();

        return $this->newParentId != $oldParentId;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @return CharacteristicQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new CharacteristicQuery(static::class);
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

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        // Update the answer records record
        Craft::$app->getDb()->createCommand()
            ->update('{{%characteristic_values}}', [
                'deletedWithCharacteristic' => true,
            ], ['characteristicId' => $this->id], [], false)
            ->execute();

        $values = $this->getValues();
        if ($values instanceof ElementQueryInterface) {
            $values = $values->all();
        }
        foreach ($values as $value) {
            Craft::$app->elements->deleteElement($value);
        }

        parent::afterDelete();
    }

    /**
     * Returns the product associated with this variant.
     *
     * @param ElementInterface|null $element
     * @return CharacteristicValue[] The product associated with this variant, or null if it isn’t known
     */
    public function getValues(ElementInterface $element = null)
    {
        if ($element) {
            if (!isset($this->_elementValues[$element->id])) {
                $criteria['characteristicId'] = $this->id;
                $criteria['relatedTo'] = ['sourceElement' => $element];
                return Craft::configure(CharacteristicValue::find(), $criteria);
            }
            return $this->_elementValues[$element->id];
        }

        if (null === $this->_values) {
            if ($this->id) {
                $criteria['characteristicId'] = $this->id;
                return Craft::configure(CharacteristicValue::find(), $criteria);
            }
        }

        return $this->_values;
    }

    public function setValues($values, ElementInterface $element = null)
    {
        if (!$element) {
            $this->_values = [];
        } else {
            $this->_elementValues[$element->id] = [];
        }

        if (empty($values)) {
            return;
        }

        foreach ($values as $key => $value) {
            $value->setCharacteristic($this);

            if (!$element) {
                $this->_values[] = $value;
            } else {
                $this->_elementValues[$element->id] = $value;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        $group = $this->getGroup();

        $this->fieldLayoutId = $group->characteristicFieldLayoutId;

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function beforeSave(bool $isNew): bool
    {
        // Set the structure ID for Element::attributes() and afterSave()
        $this->structureId = $this->getGroup()->structureId;

        if ($this->_hasNewParent()) {
            if ($this->newParentId) {
                $parentCategory = Plugin::$plugin->characteristics->getCharacteristicById($this->newParentId);

                if (!$parentCategory) {
                    throw new Exception('Invalid characteristic ID: ' . $this->newParentId);
                }
            } else {
                $parentCategory = null;
            }

            $this->setParent($parentCategory);
        }

        return parent::beforeSave($isNew);
    }

    // Private Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure(int $structureId)
    {
        parent::afterMoveInStructure($structureId);
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'allowCustomOptions':
                if ($this->allowCustomOptions) {
                    return '<div class="status enabled" title="' . Craft::t('app', 'Enabled') . '"></div>';
                }

                return '<div class="status" title="' . Craft::t('app', 'Not enabled') . '"></div>';
            case 'required':
                if ($this->required) {
                    return '<div class="status enabled" title="' . Craft::t('app', 'Enabled') . '"></div>';
                }

                return '<div class="status" title="' . Craft::t('app', 'Not enabled') . '"></div>';
        }

        return parent::tableAttributeHtml($attribute);
    }
}
