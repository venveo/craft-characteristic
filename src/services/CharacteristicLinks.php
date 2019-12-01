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
        $relationData = [];
        foreach ($data as $datum) {
            $relationData[] = [
                $field->id,
                $element->id,
                $element->siteId,
                $datum['characteristic']->id
            ];
            foreach($datum['values'] as $index => $value) {
                $link = new CharacteristicLink();
                $link->characteristicId = $datum['characteristic']->id;
                $link->valueId = $value->id;
                $link->sortOrder = $index;
                $link->elementId = $element->id;
                $link->fieldId = $field->id;
                $link->save();

                $relationData[] = [
                    $field->id,
                    $element->id,
                    $element->siteId,
                    $link->valueId = $value->id
                ];
            }
        }

        // Delete the relations and re-save them
        Craft::$app->getDb()->createCommand()
            ->delete(Table::RELATIONS, ['fieldId' => $field->id, 'sourceId' => $element->id, 'sourceSiteId' => $element->siteId])->execute();

        if (!empty($relationData)) {
            Craft::$app->getDb()->createCommand()
                ->batchInsert(
                    Table::RELATIONS,
                    ['fieldId', 'sourceId', 'sourceSiteId', 'targetId'],
                    $relationData)
                ->execute();
        }
    }
}
