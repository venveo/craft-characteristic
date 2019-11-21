<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\errors\SectionNotFoundException;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\Section;
use Throwable;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\events\CharacteristicGroupEvent;
use venveo\characteristic\models\CharacteristicGroup;
use venveo\characteristic\records\CharacteristicGroup as CharacteristicGroupRecord;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicGroups extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event SectionEvent The event that is triggered before a group is saved.
     */
    const EVENT_BEFORE_SAVE_GROUP = 'beforeSaveGroup';

    /**
     * @event SectionEvent The event that is triggered after a group is saved.
     */
    const EVENT_AFTER_SAVE_GROUP = 'afterSaveGroup';

    /**
     * @event SectionEvent The event that is triggered before a group is deleted.
     */
    const EVENT_BEFORE_DELETE_GROUP = 'beforeDeleteGroup';

    /**
     * @event SectionEvent The event that is triggered before a group delete is applied to the database.
     * @since 3.1.0
     */
    const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event SectionEvent The event that is triggered after a group is deleted.
     */
    const EVENT_AFTER_DELETE_GROUP = 'afterDeleteGroup';

    const CONFIG_GROUPS_KEY = 'characteristicGroups';

    // Properties
    // =========================================================================

    /**
     * @var CharacteristicGroup[]
     */
    private $_groups;


    // Public Methods
    // =========================================================================

    /**
     * Returns all of the group IDs.
     *
     * ---
     *
     * ```php
     * $groupIds = Craft::$app->sections->allSectionIds;
     * ```
     * ```twig
     * {% set groupIds = craft.app.sections.allSectionIds %}
     * ```
     *
     * @return int[] All the groups’ IDs.
     */
    public function getAllGroupIds(): array
    {
        return ArrayHelper::getColumn($this->getAllGroups(), 'id', false);
    }

    /**
     * Returns all groups.
     *
     * ---
     *
     * ```php
     * $groups = Craft::$app->sections->allSections;
     * ```
     * ```twig
     * {% set groups = craft.app.sections.allSections %}
     * ```
     *
     * @return CharacteristicGroup[] All the groups.
     */
    public function getAllGroups(): array
    {
        if ($this->_groups !== null) {
            return $this->_groups;
        }

        $results = $this->_createGroupQuery()
            ->all();

        $this->_groups = [];

        foreach ($results as $result) {
            $this->_groups[] = new CharacteristicGroup($result);
        }

        return $this->_groups;
    }

    /**
     * Returns a Query object prepped for retrieving groups.
     *
     * @return Query
     */
    private function _createGroupQuery(): Query
    {
        $query = (new Query())
            ->select([
                'groups.id',
                'groups.name',
                'groups.handle',
                'groups.characteristicFieldLayoutId',
                'groups.valueFieldLayoutId',
                'groups.uid',
            ])
            ->from(['{{%characteristic_groups}} groups']);

        return $query;
    }

    /**
     * Returns all of the group IDs that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $groupIds = Craft::$app->sections->editableSectionIds;
     * ```
     * ```twig
     * {% set groupIds = craft.app.sections.editableSectionIds %}
     * ```
     *
     * @return int[] All the editable groups’ IDs.
     */
    public function getEditableGroupIds(): array
    {
        return ArrayHelper::getColumn($this->getEditableGroups(), 'id', false);
    }

    /**
     * Returns all editable groups.
     *
     * ---
     *
     * ```php
     * $groups = Craft::$app->sections->editableSections;
     * ```
     * ```twig
     * {% set groups = craft.app.sections.editableSections %}
     * ```
     *
     * @return CharacteristicGroup[] All the editable groups.
     */
    public function getEditableGroups(): array
    {
        $userSession = Craft::$app->getUser();
        return ArrayHelper::where($this->getAllGroups(), function (CharacteristicGroup $group) use ($userSession) {
            return $userSession->checkPermission('editCharacteristics:' . $group->uid);
        });
    }

    /**
     * Gets the total number of groups.
     *
     * ---
     *
     * ```php
     * $total = Craft::$app->sections->totalSections;
     * ```
     * ```twig
     * {% set total = craft.app.sections.totalSections %}
     * ```
     *
     * @return int
     */
    public function getTotalGroups(): int
    {
        return count($this->getAllGroups());
    }

    /**
     * Gets the total number of groups that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $total = Craft::$app->sections->totalEditableSections;
     * ```
     * ```twig
     * {% set total = craft.app.sections.totalEditableSections %}
     * ```
     *
     * @return int
     */
    public function getTotalEditableGroups(): int
    {
        return count($this->getEditableGroups());
    }

    /**
     * Gets a group by its UID.
     *
     * ---
     *
     * ```php
     * $group = Craft::$app->sections->getSectionByUid('b3a9eef3-9444-4995-84e2-6dc6b60aebd2');
     * ```
     * ```twig
     * {% set group = craft.app.sections.getSectionByUid('b3a9eef3-9444-4995-84e2-6dc6b60aebd2') %}
     * ```
     *
     * @param string $uid
     * @return CharacteristicGroup|null
     * @since 3.1.0
     */
    public function getGroupByUid(string $uid)
    {
        return ArrayHelper::firstWhere($this->getAllGroups(), 'uid', $uid);
    }

    /**
     * Gets a group by its handle.
     *
     * ---
     *
     * ```php
     * $group = Craft::$app->sections->getSectionByHandle('news');
     * ```
     * ```twig
     * {% set group = craft.app.sections.getSectionByHandle('news') %}
     * ```
     *
     * @param string $groupHandle
     * @return CharacteristicGroup|null
     */
    public function getGroupByHandle(string $groupHandle)
    {
        return ArrayHelper::firstWhere($this->getAllGroups(), 'handle', $groupHandle);
    }


    /**
     * Saves a group.
     *
     * ---
     *
     * ```php
     * use craft\models\Section;
     * use craft\models\Section_SiteSettings;
     *
     * $group = new Section([
     *     'name' => 'News',
     *     'handle' => 'news',
     * ]);
     *
     * $success = Craft::$app->sections->saveSection($section);
     * ```
     *
     * @param CharacteristicGroup $group The group to be saved
     * @param bool $runValidation Whether the section should be validated
     * @return bool
     * @throws SectionNotFoundException if $section->id is invalid
     * @throws Throwable if reasons
     */
    public function saveGroup(CharacteristicGroup $group, bool $runValidation = true): bool
    {
        $isNewGroup = !$group->id;

        // Fire a 'beforeSaveSection' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_GROUP, new CharacteristicGroupEvent([
                'group' => $group,
                'isNew' => $isNewGroup
            ]));
        }

        if ($runValidation && !$group->validate()) {
            Craft::info('Group not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewGroup) {
            $group->uid = StringHelper::UUID();
        } else if (!$group->uid) {
            $group->uid = Db::uidById(CharacteristicGroupRecord::tableName(), $group->id);
        }

        // Assemble the section config
        // -----------------------------------------------------------------

        $projectConfig = Craft::$app->getProjectConfig();

        $configData = [
            'name' => $group->name,
            'handle' => $group->handle
        ];

        $generateLayoutConfig = function(FieldLayout $fieldLayout): array {
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

        $configData['characteristicFieldLayouts'] = $generateLayoutConfig($group->getCharacteristicFieldLayout());
        $configData['valueFieldLayouts'] = $generateLayoutConfig($group->getValueFieldLayout());

        // Do everything that follows in a transaction so no DB changes will be
        // saved if an exception occurs that ends up preventing the project config
        // changes from getting saved
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Save the group config
            // -----------------------------------------------------------------

            $configPath = self::CONFIG_GROUPS_KEY . '.' . $group->uid;
            $projectConfig->set($configPath, $configData);

            if ($isNewGroup) {
                $group->id = Db::idByUid(CharacteristicGroupRecord::tableName(), $group->uid);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Handle group change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedGroup(ConfigEvent $event)
    {
        $groupUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields and sites are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Basic data
            $groupRecord = $this->_getGroupRecord($groupUid);
            $isNewGroup = $groupRecord->getIsNewRecord();
            $fieldsService = Craft::$app->getFields();

            $groupRecord->uid = $groupUid;
            $groupRecord->name = $data['name'];
            $groupRecord->handle = $data['handle'];

            if (!empty($data['characteristicFieldLayouts']) && !empty($config = reset($data['characteristicFieldLayouts']))) {
                // Save the characteristic field layout
                $layout = FieldLayout::createFromConfig($config);
                $layout->id = $groupRecord->characteristicFieldLayoutId;
                $layout->type = Characteristic::class;
                $layout->uid = key($data['characteristicFieldLayouts']);
                $fieldsService->saveLayout($layout);
                $groupRecord->characteristicFieldLayoutId = $layout->id;
            } else if ($groupRecord->characteristicFieldLayoutId) {
                // Delete the main field layout
                $fieldsService->deleteLayoutById($groupRecord->characteristicFieldLayoutId);
                $groupRecord->characteristicFieldLayoutId = null;
            }

            if (!empty($data['valueFieldLayouts']) && !empty($config = reset($data['valueFieldLayouts']))) {
                // Save the variant field layout
                $layout = FieldLayout::createFromConfig($config);
                $layout->id = $groupRecord->valueFieldLayoutId;
                $layout->type = CharacteristicValue::class;
                $layout->uid = key($data['valueFieldLayouts']);
                $fieldsService->saveLayout($layout);
                $groupRecord->valueFieldLayoutId = $layout->id;
            } else if ($groupRecord->valueFieldLayoutId) {
                // Delete the variant field layout
                $fieldsService->deleteLayoutById($groupRecord->valueFieldLayoutId);
                $groupRecord->valueFieldLayoutId = null;
            }

            $groupRecord->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_groups = null;

        /** @var Section $group */
        $group = $this->getGroupById($groupRecord->id);

        // Fire an 'afterSaveSection' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_GROUP, new CharacteristicGroupEvent([
                'group' => $group,
                'isNew' => $isNewGroup
            ]));
        }
    }

    /**
     * Gets a group's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed groups in search
     * @return CharacteristicGroupRecord
     */
    private function _getGroupRecord(string $uid): CharacteristicGroupRecord
    {
        $query = CharacteristicGroupRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new CharacteristicGroupRecord();
    }

    /**
     * Returns a group by its ID.
     *
     * ---
     *
     * ```php
     * $group = Craft::$app->sections->getSectionById(1);
     * ```
     * ```twig
     * {% set group = craft.app.sections.getSectionById(1) %}
     * ```
     *
     * @param int $groupId
     * @return CharacteristicGroup|null
     */
    public function getGroupById(int $groupId)
    {
        return ArrayHelper::firstWhere($this->getAllGroups(), 'id', $groupId);
    }

    /**
     * Deletes a group by its ID.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->sections->deleteSectionById(1);
     * ```
     *
     * @param int $groupId
     * @return bool Whether the section was deleted successfully
     * @throws Throwable if reasons
     */
    public function deleteGroupById(int $groupId): bool
    {
        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        return $this->deleteGroup($group);
    }


    // Private Methods
    // =========================================================================

    /**
     * Deletes a group.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->sections->deleteSection($section);
     * ```
     *
     * @param CharacteristicGroup $group
     * @return bool Whether the group was deleted successfully
     * @throws Throwable if reasons
     */
    public function deleteGroup(CharacteristicGroup $group): bool
    {
        // Fire a 'beforeDeleteSection' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_GROUP, new CharacteristicGroupEvent([
                'group' => $group,
            ]));
        }

        // Remove the group from the project config
        Craft::$app->getProjectConfig()->remove(self::CONFIG_GROUPS_KEY . '.' . $group->uid);
        return true;
    }

    /**
     * Handle a group getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedGroup(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $groupRecord = $this->_getGroupRecord($uid);

        if (!$groupRecord->id) {
            return;
        }

        /** @var CharacteristicGroup $group */
        $group = $this->getGroupById($groupRecord->id);

        // Fire a 'beforeApplySectionDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_GROUP_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_GROUP_DELETE, new CharacteristicGroupEvent([
                'group' => $group,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $characteristics = Characteristic::find()
                ->groupId($group->id)
                ->limit(null)
                ->all();

            foreach($characteristics as $characteristic) {
                Craft::$app->getElements()->deleteElement($characteristic);
            }

            $characteristicFieldLayoutId = $group->characteristicFieldLayoutId;
            $valueFieldLayoutId = $group->valueFieldLayoutId;

            if ($characteristicFieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($characteristicFieldLayoutId);
            }
            if ($valueFieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($valueFieldLayoutId);
            }

            $groupRecord->delete();
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_groups = null;

        // Fire an 'afterDeleteSection' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_GROUP, new CharacteristicGroupEvent([
                'group' => $group,
            ]));
        }
    }
}
