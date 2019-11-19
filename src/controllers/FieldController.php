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
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\web\Controller;
use venveo\characteristic\assetbundles\characteristicelement\CharacteristicElement;
use venveo\characteristic\Characteristic as Plugin;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\db\CharacteristicQuery;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class FieldController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionGetCharacteristicsForSource($sourceKey) {
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
