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
 */
class Characteristic extends Element
{
    // Public Properties
    // =========================================================================
    public $handle = '';

    public $groupId = null;

    // Static Methods
    // =========================================================================
    /**
     * @var \craft\base\ElementInterface[]|string|null
     */
    private $_values;

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
     * @return CharacteristicQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new CharacteristicQuery(static::class);
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
                ]
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
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['groupId'], 'number', 'integerOnly' => true];
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
                ->all();

            return [
                'elementType' => CharacteristicValue::class,
                'map' => $map
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    public function setValues($values) {
        $this->_values = [];
        $count = 1;

        if (empty($values)) {
            return;
        }

        foreach ($values as $key => $value) {
            if (!$value instanceof CharacteristicValue) {
                die('wtf');
//                $variant = ProductHelper::populateProductVariantModel($this, $variant, $key);
            }
//            $variant->sortOrder = $count++;
            $value->setCharacteristic($this);


            $this->_values[] = $value;
        }
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
     * Returns the product associated with this variant.
     *
     * @return CharacteristicValue[] The product associated with this variant, or null if it isn’t known
     * @throws InvalidConfigException if the product ID is missing from the variant
     */
    public function getValues()
    {
        if (null === $this->_values) {
            if ($this->id) {
                $criteria['characteristicId'] = $this->id;
                return Craft::configure(CharacteristicValue::find(), $criteria);
            }
        }

        return $this->_values;
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

    // Indexes, etc.
    // -------------------------------------------------------------------------

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

    // Events
    // -------------------------------------------------------------------------
// Events
    // -------------------------------------------------------------------------

    /**
     * @param string $type
     */
    public function getRelatedElements($type = Entry::class)
    {
        $linkQuery = CharacteristicLink::find();
        $linkQuery->where(['characteristicId' => $this->id]);
        $ids = $linkQuery->select('elementId')->indexBy('elementId')->all();
        $criteria['id'] = array_keys($ids);
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


    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        $group = $this->getGroup();

        $this->fieldLayoutId = $group->characteristicFieldLayoutId;

        return parent::beforeValidate();
    }
}
