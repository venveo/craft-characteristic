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
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;

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
}
