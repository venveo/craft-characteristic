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
use Throwable;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\records\CharacteristicValue as CharacteristicValueRecord;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicValues extends Component
{
    // Constants
    // =========================================================================

    // Properties
    // =========================================================================

    // Characteristics
    // -------------------------------------------------------------------------

    /**
     * Creates a value for a characteristic or returns an existing one
     * @param Characteristic $characteristic
     * @param $value
     * @param bool $idempotent
     * @return array|\craft\base\ElementInterface|CharacteristicValue|null
     * @throws Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function getOrCreateValueElement(Characteristic $characteristic, $value, $idempotent = false)
    {
        $existing = CharacteristicValue::find()->value($value)->characteristicId($characteristic->id)->one();
        if ($existing) {
            return $existing;
        }

        $sortOrder = 0;
        $nextItem = \venveo\characteristic\records\CharacteristicValue::find()
            ->addSelect('MAX(sortOrder) as sort')
            ->where(['characteristicId' => $characteristic->id])
            ->scalar();
        if ($nextItem !== null) {
            $sortOrder = $nextItem + 1;
        }

        $characteristicValue = new CharacteristicValue();
        $characteristicValue->value = $value;
        $characteristicValue->idempotent = $idempotent;
        $characteristicValue->sortOrder = $sortOrder;
        $characteristicValue->characteristicId = $characteristic->id;
        Craft::$app->elements->saveElement($characteristicValue);
        return $characteristicValue;
    }

    /**
     * Reorders entry types.
     *
     * @param array $valueIds
     * @return bool Whether the entry types were reordered successfully
     */
    public function reorderValues(array $valueIds): bool
    {
        foreach ($valueIds as $sortOrder => $valueId) {
            $value = CharacteristicValueRecord::findOne($valueId);
            $value->sortOrder = $sortOrder;
            $value->save();
        }
        return true;
    }

    public function deleteValueById(int $valueId): bool
    {
        $value = $this->getCharacteristicValueById($valueId);

        if (!$value) {
            return false;
        }

        return $this->deleteValue($value);
    }

    /**
     * Returns a category by its ID.
     *
     * @param int $characteristicValueId
     * @return Characteristic|null
     */
    public function getCharacteristicValueById(int $characteristicValueId)
    {
        if (!$characteristicValueId) {
            return null;
        }


        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($characteristicValueId, CharacteristicValue::class);
    }

    public function deleteValue(CharacteristicValue $value): bool
    {
        Craft::$app->elements->deleteElement($value);
        return true;
    }
}
