<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\variables;

use craft\elements\db\ElementQueryInterface;
use venveo\characteristic\Characteristic;

use Craft;
use venveo\characteristic\elements\db\CharacteristicQuery;
use venveo\characteristic\helpers\Drilldown;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param array $criteria
     * @return CharacteristicQuery
     */
    public function characteristics(array $criteria = []): CharacteristicQuery
    {
        $query = \venveo\characteristic\elements\Characteristic::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    public function drilldown($group, ElementQueryInterface $query) {
        $drilldown = new Drilldown([
            'group' => $group,
            'query' => $query
        ]);

        return $drilldown;
    }
}
