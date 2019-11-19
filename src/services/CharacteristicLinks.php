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

use craft\base\Component;
use venveo\characteristic\records\CharacteristicLink;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicLinks extends Component
{
    // Constants
    // =========================================================================

    // Properties
    // =========================================================================

    // Characteristics
    // -------------------------------------------------------------------------

    public function resaveLinks($data, $element, $field)
    {
        // First we need to flush the existing attributes...
        $query = CharacteristicLink::find();
        $query->where(['elementId' => $element->id])
            ->andWhere(['fieldId' => $field->id]);
        $results = $query->all();
        foreach ($results as $result) {
            $result->delete();
        }
        foreach ($data as $datum) {
            $link = new CharacteristicLink();
            $link->characteristicId = $datum['characteristic']->id;
            $link->valueId = $datum['value']->id;
            $link->elementId = $element->id;
            $link->fieldId = $field->id;
            $link->save();
        }
    }
}
