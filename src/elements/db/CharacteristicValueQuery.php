<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\characteristic\elements\db;

use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\elements\Tag;
use craft\helpers\Db;
use craft\models\TagGroup;
use venveo\characteristic\models\CharacteristicGroup;
use venveo\characteristic\records\CharacteristicGroup as CharacteristicGroupRecord;
use yii\db\Connection;

class CharacteristicValueQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['text' => SORT_ASC];

    // General parameters
    // -------------------------------------------------------------------------

    public $characteristicId;

    public $text;

    // Public Methods
    // =========================================================================


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
    public function characteristicId($value)
    {
        $this->characteristicId = $value;
        return $this;
    }

    public function text($value)
    {
        $this->text = $value;
        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('characteristic_values');

        $this->query->select([
            'characteristic_values.characteristicId',
            'characteristic_values.text',
            'characteristic_values.sortOrder',
        ]);

        if ($this->characteristicId) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_values.characteristicId', $this->characteristicId));
        }
        if ($this->text) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_values.text', $this->text));
        }

        return parent::beforePrepare();
    }
}
