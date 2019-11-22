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
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Exception;
use venveo\characteristic\Characteristic as Plugin;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;
use yii\web\NotFoundHttpException;
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

    public function actionEditValue(string $groupHandle, int $characteristicId = null, int $valueId = null, CharacteristicValue $value = null): Response
    {
        $variables = [
            'characteristicId' => $characteristicId,
            'valueId' => $valueId,
            'value' => $value
        ];

        if (($response = $this->_prepEditCharacteristicValueVariables($variables)) !== null) {
            return $response;
        }


        /** @var Characteristic $characteristic */
        $characteristic = $variables['characteristic'];

        /** @var CharacteristicValue $group */
        $value = $variables['value'];

        $group = $characteristic->getGroup();

        // Body class
        $variables['bodyClass'] = 'edit-characteristic-value';

        // Page title
        if ($value->id === null) {
            $variables['title'] = Craft::t('characteristic', 'Create a new Characteristic Value');
        } else {
            $variables['docTitle'] = $variables['title'] = trim($value->value) ?: Craft::t('characteristic', 'Edit Characteristic Value');
        }
        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('characteristic', 'Characteristics'),
                'url' => UrlHelper::url('characteristics')
            ]
        ];

        $variables['crumbs'][] = [
            'label' => Craft::t('site', $group->name),
            'url' => UrlHelper::url('characteristics/' . $group->handle)
        ];

        $variables['crumbs'][] = [
            'label' => $characteristic->title,
            'url' => UrlHelper::url('characteristics/' . $group->handle . '/' . $characteristic->id)
        ];

        $tabs = [];
        $tabs['characteristicValueFields'] = [
            'label' => Craft::t('characteristic', 'Content'),
            'url' => '#fields',
        ];

        $variables['tabs'] = $tabs;
        $variables['selectedTab'] = 'characteristicValueFields';


        // Render the template!
        return $this->renderTemplate('characteristic/characteristics/_edit-value', $variables);
    }

    /**
     * @return mixed
     */
    public function actionReorderValue(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $valueIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
        if (!is_array($valueIds)) {
            throw new Exception('Expected array of ids');
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

    private function _prepEditCharacteristicValueVariables(array &$variables)
    {
        // Get the value
        // ---------------------------------------------------------------------

        if (!empty($variables['characteristicId'])) {
            $variables['characteristic'] = Plugin::$plugin->characteristics->getCharacteristicById($variables['characteristicId']);
        }

        if (empty($variables['characteristicId'])) {
            throw new NotFoundHttpException('Characteristic not found');
        }

        // Get the characteristic value
        // ---------------------------------------------------------------------

        if (empty($variables['value'])) {
            if (!empty($variables['valueId'])) {

                $variables['value'] = Plugin::$plugin->characteristicValues->getCharacteristicValueById($variables['valueId']);

                if (!$variables['value']) {
                    throw new NotFoundHttpException('Characteristic value not found');
                }
            } else {
                $variables['value'] = new CharacteristicValue();
                $variables['value']->characteristicId = $variables['characteristic']->id;
            }
        }
        return null;
    }

    /**
     * Saves an entry.
     *
     * @param bool $duplicate Whether the entry should be duplicated
     * @return Response|null
     * @throws ServerErrorHttpException if reasons
     * @throws NotFoundHttpException
     */
    public function actionSaveValue()
    {
        $this->requirePostRequest();

        $characteristicValue = $this->_getValueModel();
        $request = Craft::$app->getRequest();

        // Permission enforcement
//        $this->enforceEditEntryPermissions($entry, $duplicate);
        $currentUser = Craft::$app->getUser()->getIdentity();

        // Populate the entry with post data
        $this->_populateCharacteristicValueModel($characteristicValue);


        if (!Craft::$app->getElements()->saveElement($characteristicValue)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $characteristicValue->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save characteristic value.'));

            // Send the entry back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'value' => $characteristicValue
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $characteristicValue->id;
            $return['value'] = $characteristicValue->value;

            if ($request->getIsCpRequest()) {
                $return['cpEditUrl'] = $characteristicValue->getCpEditUrl();
            }

            $return['dateCreated'] = DateTimeHelper::toIso8601($characteristicValue->dateCreated);
            $return['dateUpdated'] = DateTimeHelper::toIso8601($characteristicValue->dateUpdated);

            return $this->asJson($return);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Characteristic value saved.'));

        return $this->redirectToPostedUrl($characteristicValue);
    }

    private function _getValueModel()
    {
        $request = Craft::$app->getRequest();
        $valueId = $request->getBodyParam('valueId');
        if ($valueId) {
            $value = null;
            $value = Plugin::$plugin->characteristicValues->getCharacteristicValueById($valueId);

            if (!$value) {
                throw new NotFoundHttpException('Characteristic value not found');
            }
        } else {
            $value = new CharacteristicValue();
            $value->characteristicId = $request->getRequiredBodyParam('characteristicId');
        }

        return $value;
    }

    private function _populateCharacteristicValueModel(CharacteristicValue $value)
    {
        $request = Craft::$app->getRequest();

        $value->value = $request->getBodyParam('value', $value->value);
        $value->setFieldValuesFromRequest('fields');
    }
}
