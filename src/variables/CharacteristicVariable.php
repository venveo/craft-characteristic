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

use Craft;
use craft\elements\db\ElementQueryInterface;
use nystudio107\pluginvite\variables\ViteVariableInterface;
use nystudio107\pluginvite\variables\ViteVariableTrait;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicLinkBlock;
use venveo\characteristic\elements\db\CharacteristicLinkBlockQuery;
use venveo\characteristic\elements\db\CharacteristicQuery;
use venveo\characteristic\helpers\Drilldown;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicVariable implements ViteVariableInterface
{
    use ViteVariableTrait;

    /**
     * @param array $criteria
     * @return CharacteristicQuery
     */
    public function characteristics(array $criteria = []): CharacteristicQuery
    {
        $query = Characteristic::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    public function characteristicLinkBlocks(array $criteria = []): CharacteristicLinkBlockQuery
    {
        $query = CharacteristicLinkBlock::find();
        Craft::configure($query, $criteria);
        return $query;
    }

    public function drilldown($group, ElementQueryInterface $query, $options = [])
    {
        $drilldown = new Drilldown(array_merge([
            'group' => $group,
            'query' => $query
        ], $options));

        return $drilldown;
    }
}
