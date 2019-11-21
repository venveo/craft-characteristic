<?php

namespace venveo\characteristic\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class CharacteristicValueQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $characteristicId;

    // General parameters
    // -------------------------------------------------------------------------
    public $value;
    public $sortOrder;
    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['characteristic_values.sortOrder' => SORT_ASC];

    // Public Methods
    // =========================================================================

    public function characteristicId($value)
    {
        $this->characteristicId = $value;
        return $this;
    }

    public function value($value)
    {
        $this->value = $value;
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
            'characteristic_values.value',
            'characteristic_values.sortOrder',
        ]);

        if ($this->characteristicId) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_values.characteristicId', $this->characteristicId));
        }
        if ($this->value) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_values.value', $this->text));
        }

        return parent::beforePrepare();
    }
}
