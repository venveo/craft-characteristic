<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\characteristic\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use craft\models\TagGroup;
use venveo\characteristic\models\CharacteristicGroup;
use venveo\characteristic\records\CharacteristicGroup as CharacteristicGroupRecord;

class CharacteristicQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    /**
     * @var int|int[]|null The tag group ID(s) that the resulting tags must be in.
     * ---
     * ```php
     * // fetch tags in the Topics group
     * $tags = \craft\elements\Tag::find()
     *     ->group('topics')
     *     ->all();
     * ```
     * ```twig
     * {# fetch tags in the Topics group #}
     * {% set tags = craft.tags()
     *     .group('topics')
     *     .all() %}
     * ```
     * @used-by group()
     * @used-by groupId()
     */
    public $groupId;

    // General parameters
    // -------------------------------------------------------------------------
    public $handle;
    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['title' => SORT_ASC];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($name === 'group') {
            $this->group($value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Narrows the query results based on the tag groups the tags belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | in a group with a handle of `foo`.
     * | `'not foo'` | not in a group with a handle of `foo`.
     * | `['foo', 'bar']` | in a group with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a group with a handle of `foo` or `bar`.
     * | a [[TagGroup|TagGroup]] object | in a group represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the Foo group #}
     * {% set {elements-var} = {twig-method}
     *     .group('foo')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the Foo group
     * ${elements-var} = {php-method}
     *     ->group('foo')
     *     ->all();
     * ```
     *
     * @param string|string[]|TagGroup|null $value The property value
     * @return static self reference
     * @uses $groupId
     */
    public function group($value)
    {
        if ($value instanceof CharacteristicGroup) {
            $this->groupId = $value->id;
        } else if ($value !== null) {
            $this->groupId = (new Query())
                ->select(['id'])
                ->from([CharacteristicGroupRecord::tableName()])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->groupId = null;
        }

        return $this;
    }

    /**
     * Narrows the query results based on the tag groups the tags belong to, per the groups’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | in a group with an ID of 1.
     * | `'not 1'` | not in a group with an ID of 1.
     * | `[1, 2]` | in a group with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a group with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the group with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *     .groupId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the group with an ID of 1
     * ${elements-var} = {php-method}
     *     ->groupId(1)
     *     ->all();
     * ```
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $groupId
     */
    public function groupId($value)
    {
        $this->groupId = $value;
        return $this;
    }

    public function handle($value)
    {
        $this->handle = $value;
        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        // See if 'group' was set to an invalid handle
        if ($this->groupId === []) {
            return false;
        }

        $this->joinElementTable('characteristic_characteristics');

        $this->query->select([
            'characteristic_characteristics.groupId',
            'characteristic_characteristics.handle'
        ]);

        if ($this->groupId) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_characteristics.groupId', $this->groupId));
        }
        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_characteristics.handle', $this->handle));
        }

        return parent::beforePrepare();
    }
}
