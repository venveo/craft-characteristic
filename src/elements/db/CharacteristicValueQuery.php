<?php

namespace venveo\characteristic\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use venveo\characteristic\elements\Characteristic;

class CharacteristicValueQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $characteristicId;

    // General parameters
    // -------------------------------------------------------------------------
    public $value;
    public $sortOrder;
    public $idempotent;
    public $deletedWithCharacteristic;

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

    public function characteristic($value)
    {
        $this->characteristicId = $value instanceof Characteristic ? $value->id : $value;
        return $this;
    }

    public function value($value)
    {
        $this->value = $value;
        return $this;
    }

    public function idempotent($value)
    {
        $this->idempotent = $value;
        return $this;
    }

    public function deletedWithCharacteristic($value)
    {
        $this->deletedWithCharacteristic = $value;
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
            'characteristic_values.idempotent',
        ]);

        if ($this->characteristicId !== null) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_values.characteristicId', $this->characteristicId));
        }
        if ($this->value !== null) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_values.value', $this->value));
        }
        if ($this->idempotent !== null) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_values.idempotent', $this->idempotent));
        }
        if ($this->deletedWithCharacteristic !== null) {
            $this->subQuery->andWhere(Db::parseParam('characteristic_values.deletedWithCharacteristic', $this->deletedWithCharacteristic));
        }

        return parent::beforePrepare();
    }
}
