<?php

namespace venveo\characteristic\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class CharacteristicValueQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $defaultOrderBy = ['characteristic_values.sortOrder' => SORT_ASC];

    // General parameters
    // -------------------------------------------------------------------------

    public $characteristicId;

    public $text;

    public $sortOrder;

    // Public Methods
    // =========================================================================


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
