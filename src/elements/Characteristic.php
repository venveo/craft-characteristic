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
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use venveo\characteristic\Characteristic as Plugin;
use venveo\characteristic\elements\db\CharacteristicQuery;
use venveo\characteristic\elements\db\CharacteristicValueQuery;
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

    /**
     * @var string
     */
    public $title = '';

    public $handle = '';

    public $groupId = null;

    // Static Methods
    // =========================================================================

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
        return null;
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

    public function getValues($criteria = []) {
        $criteria['characteristicId'] = $this->id;
        $query = Craft::configure(CharacteristicValue::find(), $criteria);
        return $query;
    }

    /**
     * @param string $type
     */
    public function getRelatedElements($type = Entry::class) {
        $linkQuery = CharacteristicLink::find();
        $linkQuery->where(['characteristicId' => $this->id]);
        $ids = $linkQuery->select('elementId')->indexBy('elementId')->all();
        $criteria['id'] = array_keys($ids);
        $query = Craft::configure($type::find(), $criteria);
        return $query;
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
            $record = CharacteristicRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid characteristic ID: ' . $this->id);
            }
        } else {
            $record = new CharacteristicRecord();
            $record->id = (int)$this->id;
        }

        $record->groupId = $this->groupId;
        $record->title = $this->title;
        $record->handle = $this->handle;

        $record->save(false);

        parent::afterSave($isNew);
    }


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
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        return true;
    }
}
