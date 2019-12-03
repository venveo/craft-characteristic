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
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Table;
use craft\errors\ElementNotFoundException;
use Throwable;
use venveo\characteristic\elements\CharacteristicLink;
use venveo\characteristic\records\CharacteristicLink as CharacteristicLinkRecord;
use yii\db\Exception;

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

    /**
     * Takes an array "data":
     * [
     *  [
     *    characteristic Characteristic
     *    values []CharacteristicValues
     *  ]
     * ]
     * and updates and saves all links and relationships
     * @param $data
     * @param $element
     * @param $field
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws \yii\base\Exception
     * @throws Exception
     */
    public function resaveLinks($data, ElementInterface $element, Field $field)
    {
        // First we need to flush the existing attributes...
        $query = CharacteristicLinkRecord::find();
        $query->select(['id'])
            ->where(['ownerId' => $element->id])
            ->andWhere(['fieldId' => $field->id]);
        $results = $query->column();
        foreach($results as $result) {
            Craft::$app->elements->deleteElementById((int)$result, CharacteristicLink::class, null, true);
        }

        $relationData = [];
        foreach ($data as $datum) {
            // Create a relationship for the element to the characteristic element
            $relationData[] = [
                $field->id,
                $element->id,
                $datum['characteristic']->id
            ];
            foreach($datum['values'] as $index => $value) {
                $link = new CharacteristicLink();
                $link->characteristicId = $datum['characteristic']->id;
                $link->valueId = $value->id;
                $link->ownerId = $element->id;
                $link->fieldId = $field->id;
                Craft::$app->elements->saveElement($link, false);

                // Create a relationship from the element to the value element
                $relationData[] = [
                    $field->id,
                    $element->id,
                    $link->valueId = $value->id
                ];
            }
        }

        // Delete the relations and re-save them
        Craft::$app->getDb()->createCommand()
            ->delete(Table::RELATIONS, ['fieldId' => $field->id, 'sourceId' => $element->id])->execute();

        if (!empty($relationData)) {
            Craft::$app->getDb()->createCommand()
                ->batchInsert(
                    Table::RELATIONS,
                    ['fieldId', 'sourceId', 'targetId'],
                    $relationData)
                ->execute();
        }
    }
}
