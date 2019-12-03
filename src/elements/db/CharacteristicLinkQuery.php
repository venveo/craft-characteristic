<?php

namespace venveo\characteristic\elements\db;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\fields\Characteristics as CharacteristicsField;

class CharacteristicLinkQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    /**
     * @var int|int[]|null The owner element ID(s) that the resulting characteristic links must belong to.
     * @used-by owner()
     * @used-by ownerId()
     */
    public $ownerId;

    /**
     * @var int|int[]|null The characteristic the relationship must have
     * @used-by characteristic()
     * @used-by characteristicId()
     */
    public $characteristicId;

    /**
     * @var int|int[]|null The value the relationship must have
     * @used-by value()
     * @used-by valueId()
     */
    public $valueId;

    /**
     * @var int|int[]|string|false|null The field ID(s) that the resulting characteristic must belong to.
     * @used-by fieldId()
     */
    public $fieldId;

    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['dateCreated' => SORT_DESC];

    // Public Methods
    // =========================================================================


    /**
     * Narrows the query results based on the field the Matrix blocks belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | in a field with a handle of `foo`.
     * | `'not foo'` | not in a field with a handle of `foo`.
     * | `['foo', 'bar']` | in a field with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a field with a handle of `foo` or `bar`.
     * | a [[MatrixField]] object | in a field represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the Foo field #}
     * {% set {elements-var} = {twig-method}
     *     .field('foo')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the Foo field
     * ${elements-var} = {php-method}
     *     ->field('foo')
     *     ->all();
     * ```
     *
     * @param string|string[]|CharacteristicsField|null $value The property value
     * @return static self reference
     * @uses $fieldId
     */
    public function field($value)
    {
        if ($value instanceof CharacteristicsField) {
            $this->fieldId = $value->id;
        } else if (is_string($value) || (is_array($value) && count($value) === 1)) {
            if (!is_string($value)) {
                $value = reset($value);
            }
            $field = Craft::$app->getFields()->getFieldByHandle($value);
            if ($field && $field instanceof CharacteristicsField) {
                $this->fieldId = $field->id;
            } else {
                $this->fieldId = false;
            }
        } else if ($value !== null) {
            $this->fieldId = (new Query())
                ->select(['id'])
                ->from([Table::FIELDS])
                ->where(Db::parseParam('handle', $value))
                ->andWhere(['type' => CharacteristicsField::class])
                ->column();
        } else {
            $this->fieldId = null;
        }

        return $this;
    }


    /**
     * Narrows the query results based on the field the Matrix blocks belong to, per the fields’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | in a field with an ID of 1.
     * | `'not 1'` | not in a field with an ID of 1.
     * | `[1, 2]` | in a field with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a field with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the field with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *     .fieldId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the field with an ID of 1
     * ${elements-var} = {php-method}
     *     ->fieldId(1)
     *     ->all();
     * ```
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $fieldId
     */
    public function fieldId($value)
    {
        $this->fieldId = $value;
        return $this;
    }


    /**
     * Narrows the query results based on the owner element of the Matrix blocks, per the owners’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `'not 1'` | not created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     * | `['not', 1, 2]` | not created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *     .ownerId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for an element with an ID of 1
     * ${elements-var} = {php-method}
     *     ->ownerId(1)
     *     ->all();
     * ```
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $ownerId
     */
    public function ownerId($value)
    {
        $this->ownerId = $value;
        return $this;
    }


    /**
     * Sets the [[ownerId()]] and [[siteId()]] parameters based on a given element.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for this entry #}
     * {% set {elements-var} = {twig-method}
     *     .owner(myEntry)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for this entry
     * ${elements-var} = {php-method}
     *     ->owner($myEntry)
     *     ->all();
     * ```
     *
     * @param ElementInterface $owner The owner element
     * @return static self reference
     * @uses $ownerId
     */
    public function owner(ElementInterface $owner)
    {
        /** @var Element $owner */
        $this->ownerId = $owner->id;
        $this->siteId = $owner->siteId;
        return $this;
    }

    public function value($value)
    {
        if ($value instanceof CharacteristicValue) {
            $this->valueId = $value->id;
        } else if ($value !== null) {
            $this->valueId = (new Query())
                ->select(['id'])
                ->from(['{{%characteristic_values}}'])
                ->where(Db::parseParam('value', $value))
                ->column();
        } else {
            $this->valueId = null;
        }

        return $this;
    }

    public function valueId($value)
    {
        $this->valueId = $value;
        return $this;
    }

    public function characteristic($value)
    {
        if ($value instanceof Characteristic) {
            $this->characteristicId = $value->id;
        } else if ($value !== null) {
            $this->characteristicId = (new Query())
                ->select(['id'])
                ->from(['{{%characteristic_characteristics}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->characteristicId = null;
        }

        return $this;
    }

    public function characteristicId($value)
    {
        $this->characteristicId = $value;
        return $this;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        if ($this->fieldId !== null && empty($this->fieldId)) {
            throw new QueryAbortedException();
        }

        $this->joinElementTable('characteristic_links');

        $this->query->select([
            'characteristic_links.fieldId',
            'characteristic_links.ownerId',
            'characteristic_links.characteristicId',
            'characteristic_links.valueId',
        ]);

        if ($this->fieldId) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_links.fieldId', $this->fieldId));
        }

        if ($this->ownerId) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_links.ownerId', $this->ownerId));
        }

        if ($this->characteristicId !== null) {
            if (empty($this->characteristicId)) {
                return false;
            }

            $this->subQuery->andWhere(Db::parseParam('characteristic_links.characteristicId', $this->characteristicId));
        }

        if ($this->valueId !== null) {
            if (empty($this->valueId)) {
                return false;
            }

            $this->subQuery->andWhere(Db::parseParam('characteristic_links.valueId', $this->valueId));
        }

        // Ensure we don't get any drafts or revisions
        $this->subQuery->innerJoin(Table::ELEMENTS . ' owners', '[[owners.id]] = [[characteristic_links.ownerId]]');
        $this->subQuery->andWhere(['owners.draftId' => null]);
        $this->subQuery->andWhere(['owners.revisionId' => null]);

        return parent::beforePrepare();
    }
}
