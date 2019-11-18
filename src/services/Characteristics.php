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

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class Characteristics extends Component
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
     * @param int $characteristicId
     * @return \venveo\characteristic\elements\Characteristic|null
     */
    public function getCharacteristicById(int $characteristicId)
    {
        if (!$characteristicId) {
            return null;
        }


        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($characteristicId, \venveo\characteristic\elements\Characteristic::class);
    }

}
