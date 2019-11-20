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
use craft\db\Table;
use craft\helpers\Db;
use craft\records\Section as SectionRecord;
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

    /**
     * @param $characteristic
     * @param $text
     */
    public function getOrCreateValueElement(Characteristic $characteristic, $text)
    {
        $existing = CharacteristicValue::find()->text($text)->characteristicId($characteristic->id)->one();
        if ($existing) {
            return $existing;
        }


        $characteristicValue = new CharacteristicValue();
        $characteristicValue->text = $text;
        $characteristicValue->characteristicId = $characteristic->id;
        Craft::$app->elements->saveElement($characteristicValue);
        return $characteristicValue;
    }

    /**
     * Reorders entry types.
     *
     * @param array $entryTypeIds
     * @return bool Whether the entry types were reordered successfully
     * @throws \Throwable if reasons
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

    public function deleteValue(CharacteristicValue $value): bool
    {
        \Craft::$app->elements->deleteElement($value);
        return true;
    }
}
