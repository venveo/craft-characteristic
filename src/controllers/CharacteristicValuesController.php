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
use craft\helpers\Json;
use craft\web\Controller;
use venveo\characteristic\Characteristic as Plugin;
use yii\web\Response;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicValuesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionReorderValue(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $valueIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
        if (!is_array($valueIds)) {
            throw new \Exception('Expected array of ids');
        }
        Plugin::$plugin->characteristicValues->reorderValues($valueIds);
        return $this->asJson(['success' => true]);
    }

    /**
     * Deletes an entry type.
     *
     * @return Response
     */
    public function actionDeleteValue(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $valueId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Plugin::$plugin->characteristicValues->deleteValueById($valueId);

        return $this->asJson(['success' => true]);
    }
}
