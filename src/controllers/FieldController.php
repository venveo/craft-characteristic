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

        /** @var CharacteristicQuery $query */
        $query = Craft::configure(Characteristic::find(), $criteria);
        return $this->asJson($query->all());
    }

}
