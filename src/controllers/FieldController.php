<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\controllers;

use Craft;
use craft\helpers\ElementHelper;
use craft\web\Controller;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\elements\db\CharacteristicQuery;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class FieldController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionGetCharacteristicsForSource($sourceKey)
    {
        $source = ElementHelper::findSource(Characteristic::class, $sourceKey, 'index');
        if (!$source) {
            return $this->asErrorJson('Source not found');
        }
        $criteria = $source['criteria'];
        $criteria['with'] = ['values'];

        /** @var CharacteristicQuery $query */
        $query = Craft::configure(Characteristic::find(), $criteria);

        $results = [];
        /** @var Characteristic $characteristic */
        foreach ($query->all() as $characteristic) {
            $results[] = [
                'id' => $characteristic->id,
                'handle' => $characteristic->handle,
                'title' => $characteristic->title,
                'required' => (bool)$characteristic->required,
                'allowCustomOptions' => (bool)$characteristic->allowCustomOptions,
                'values' => array_map(function (CharacteristicValue $value) {
                    return [
                        'id' => $value->id,
                        'value' => $value->value
                    ];
                }, $characteristic->values)
            ];
        }

        return $this->asJson($results);
    }

}
