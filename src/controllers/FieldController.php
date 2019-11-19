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
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\web\Controller;
use venveo\characteristic\assetbundles\characteristicelement\CharacteristicElement;
use venveo\characteristic\Characteristic as Plugin;
use venveo\characteristic\elements\Characteristic;
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

    public function actionGetCharacteristicsForSource($source) {

    }

}
