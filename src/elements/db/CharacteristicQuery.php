<?php
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
    public $groupId;

    public $handle;

    public $required;

    public $allowCustomOptions;

    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['lft' => SORT_ASC];

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
     * @param string|string[]|TagGroup|null $value The property value
     * @return static self reference
     * @uses $groupId
     */
    public function group($value)
    {
        if ($value instanceof CharacteristicGroup) {
            $this->groupId = $value->id;
            $this->structureId = ($value->structureId ?: false);
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
     * @inheritdoc
     */
    public function init()
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }

        parent::init();
    }

    /**
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

    public function required($value)
    {
        $this->required = $value;
        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('characteristic_characteristics');

        $this->query->select([
            'characteristic_characteristics.groupId',
            'characteristic_characteristics.handle',
            'characteristic_characteristics.allowCustomOptions',
            'characteristic_characteristics.maxValues',
            'characteristic_characteristics.required',
        ]);

        $this->_applyGroupIdParam();

        if ($this->handle) {
            if (is_array($this->handle)) {
                $this->subQuery->andWhere('in', Db::parseParam('characteristic_characteristics.handle', $this->handle));
            } else {
                $this->subQuery->andWhere(Db::parseParam('characteristic_characteristics.handle', $this->handle));
            }
        }
        if ($this->required !== null) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_characteristics.required', $this->required));
        }
        if ($this->allowCustomOptions !== null) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_characteristics.allowCustomOptions', $this->required));
        }

        return parent::beforePrepare();
    }

    /**
     * Applies the 'groupId' param to the query being prepared.
     */
    private function _applyGroupIdParam()
    {
        if ($this->groupId) {
            // Should we set the structureId param?
            if ($this->structureId === null && (!is_array($this->groupId) || count($this->groupId) === 1)) {
                $structureId = (new Query())
                    ->select(['structureId'])
                    ->from('{{%characteristic_groups}}')
                    ->where(Db::parseParam('id', $this->groupId))
                    ->scalar();
                $this->structureId = (int)$structureId ?: false;
            }

            $this->subQuery->andWhere(Db::parseParam('characteristic_characteristics.groupId', $this->groupId));
        }
    }
}
