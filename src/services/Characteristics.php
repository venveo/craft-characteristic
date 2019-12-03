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
     * @return Characteristic|null
     */
    public function getCharacteristicById(int $characteristicId)
    {
        if (!$characteristicId) {
            return null;
        }


        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($characteristicId, Characteristic::class);
    }

    /**
     * @param int $groupId
     * @param string $characteristicHandle
     * @return Characteristic|null
     */
    public function getCharacteristicByHandle(int $groupId, string $characteristicHandle)
    {
        if (!$characteristicHandle) {
            return null;
        }

        return Characteristic::find()->handle($characteristicHandle)->groupId($groupId)->one();
    }
//
//    /**
//     * @param ElementQueryInterface $query
//     * @param mixed                 $value
//     * @param CharacteristicsField              $field
//     *
//     * @throws \Exception
//     */
//    public function modifyElementsQuery (ElementQueryInterface $query, $value, CharacteristicsField $field)
//    {
//        if (empty($value))
//            return;
//        Craft::dd($value);
//        /** @var ElementQuery $query */
//        $table = MapRecord::TableName;
//        $alias = MapRecord::TableNameClean . '_' . $field->handle;
//        $on = [
//            'and',
//            '[[elements.id]] = [[' . $alias . '.ownerId]]',
//            '[[elements.dateDeleted]] IS NULL',
//            '[[elements_sites.siteId]] = [[' . $alias . '.ownerSiteId]]',
//            '[[' . $alias . '.fieldId]] = ' . $field->id,
//        ];
//        $query->query->join('JOIN', $table . ' ' . $alias, $on);
//        $query->subQuery->join('JOIN', $table . ' ' . $alias, $on);
//        if ($value === ':empty:')
//        {
//            $query->query->andWhere([
//                '[[' . $alias . '.lat]]' => null,
//            ]);
//            return;
//        }
//        else if ($value === ':notempty:' || $value === 'not :empty:')
//        {
//            $query->query->andWhere([
//                'not',
//                ['[[' . $alias . '.lat]]' => null],
//            ]);
//            return;
//        }
//        $oldOrderBy = null;
//        $search = false;
//        if (!is_array($query->orderBy))
//        {
//            $oldOrderBy = $query->orderBy;
//            $query->orderBy = [];
//        }
//        // Coordinate CraftQL support
//        if (array_key_exists('coordinate', $value))
//            $value['location'] = $value['coordinate'];
//        if (array_key_exists('location', $value))
//            $search = $this->_searchLocation($query, $value, $alias);
//        if (array_key_exists('distance', $query->orderBy))
//            $this->_replaceOrderBy($query, $search);
//        if (empty($query->orderBy))
//            $query->orderBy = $oldOrderBy;
//    }
}
