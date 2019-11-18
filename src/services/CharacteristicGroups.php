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

use craft\base\Element;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\errors\SectionNotFoundException;
use craft\events\ConfigEvent;
use craft\events\SectionEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Site;
use craft\models\Structure;
use craft\queue\jobs\ResaveElements;
use craft\records\EntryType as EntryTypeRecord;
use craft\records\Section as SectionRecord;
use venveo\characteristic\Characteristic;

use Craft;
use craft\base\Component;
use venveo\characteristic\events\CharacteristicGroupEvent;
use venveo\characteristic\models\CharacteristicGroup;
use venveo\characteristic\records\CharacteristicGroup as CharacteristicGroupRecord;
use yii\base\Exception;

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
        return ArrayHelper::where($this->getAllGroups(), function(CharacteristicGroup $group) use ($userSession) {
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
     * @throws \Throwable if reasons
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
        } catch (\Throwable $e) {
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

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Basic data
            $groupRecord = $this->_getGroupRecord($groupUid, true);
            $groupRecord->uid = $groupUid;
            $groupRecord->name = $data['name'];
            $groupRecord->handle = $data['handle'];

            $isNewGroup = $groupRecord->getIsNewRecord();

            if ($groupRecord->dateDeleted) {
                $groupRecord->restore();
            } else {
                $groupRecord->save(false);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
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
     * @throws \Throwable if reasons
     */
    public function deleteGroupById(int $groupId): bool
    {
        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        return $this->deleteGroup($group);
    }

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
     * @throws \Throwable if reasons
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
            // Delete the group
            Craft::$app->getDb()->createCommand()
                ->softDelete(CharacteristicGroupRecord::tableName(), ['id' => $groupRecord->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
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


    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving groups.
     *
     * @return Query
     */
    private function _createGroupQuery(): Query
    {
        $condition = null;
        $condition = ['groups.dateDeleted' => null];

        $query = (new Query())
            ->select([
                'groups.id',
                'groups.name',
                'groups.handle',
                'groups.uid',
            ])
            ->from(['{{%characteristic_groups}} groups'])
            ->where($condition)
            ->orderBy(['name' => SORT_ASC]);

        return $query;
    }


    /**
     * Gets a group's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed groups in search
     * @return CharacteristicGroupRecord
     */
    private function _getGroupRecord(string $uid, bool $withTrashed = false): CharacteristicGroupRecord
    {
        $query = $withTrashed ? CharacteristicGroupRecord::findWithTrashed() : CharacteristicGroupRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new CharacteristicGroupRecord();
    }
}
