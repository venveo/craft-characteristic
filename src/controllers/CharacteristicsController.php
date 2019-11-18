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
use craft\base\Element;
use craft\base\Field;
use craft\db\Query;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\models\Section;
use craft\models\Site;
use craft\web\Controller;
use venveo\characteristic\assetbundles\characteristicelement\CharacteristicElement;
use venveo\characteristic\Characteristic as Plugin;
use venveo\characteristic\elements\Characteristic;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionIndex()
    {
        Craft::$app->view->registerAssetBundle(CharacteristicElement::class);
        return $this->renderTemplate('characteristic/characteristics/_index', []);
    }


    /**
     * Called when a user beings up an entry for editing before being displayed.
     *
     * @param string $groupHandle
     * @param int|null $characteristicId
     * @param Characteristic $characteristic The entry being edited, if there were any validation errors.
     * @return Response
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException if the requested site handle is invalid
     */
    public function actionEditCharacteristic(string $groupHandle, int $characteristicId = null, Characteristic $characteristic = null): Response
    {
        $variables = [
            'groupHandle' => $groupHandle,
            'characteristicId' => $characteristicId,
            'characteristic' => $characteristic
        ];

        if (($response = $this->_prepEditCharacteristicVariables($variables)) !== null) {
            return $response;
        }

        /** @var Characteristic $characteristic */
        $characteristic = $variables['characteristic'];
        /** @var Section $group */
        $group = $variables['group'];

        // Make sure they have permission to edit this entry
//        $this->enforceEditEntryPermissions($characteristic);

        $currentUser = Craft::$app->getUser()->getIdentity();

//        try {
//            if (($variables['author'] = $characteristic->getAuthor()) === null) {
//                // Default to the current user
//                $variables['author'] = $currentUser;
//            }
//        } catch (InvalidConfigException $e) {
//            // The author doesn't exist anymore
//            $variables['author'] = $currentUser;
//        }

        // Other variables
        // ---------------------------------------------------------------------

        // Body class
        $variables['bodyClass'] = 'edit-characteristic';

        // Page title
        if ($characteristic->id === null) {
            $variables['title'] = Craft::t('characteristic', 'Create a new Characteristic');
        } else {
            $variables['docTitle'] = $variables['title'] = trim($characteristic->title) ?: Craft::t('characteristic', 'Edit Characteristic');
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

        // Render the template!
        return $this->renderTemplate('characteristic/characteristics/_edit', $variables);
    }


    /**
     * Saves an entry.
     *
     * @param bool $duplicate Whether the entry should be duplicated
     * @return Response|null
     * @throws ServerErrorHttpException if reasons
     */
    public function actionSaveCharacteristic(bool $duplicate = false)
    {
        $this->requirePostRequest();

        $characteristic = $this->_getCharacteristicModel();
        $request = Craft::$app->getRequest();

        // Permission enforcement
//        $this->enforceEditEntryPermissions($entry, $duplicate);
        $currentUser = Craft::$app->getUser()->getIdentity();

        // Populate the entry with post data
        $this->_populateCharacteristicModel($characteristic);


        if (!Craft::$app->getElements()->saveElement($characteristic)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $characteristic->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save characteristic.'));

            // Send the entry back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'characteristic' => $characteristic
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $characteristic->id;
            $return['title'] = $characteristic->title;
            $return['handle'] = $characteristic->handle;

            if ($request->getIsCpRequest()) {
                $return['cpEditUrl'] = $characteristic->getCpEditUrl();
            }

            if (($author = $characteristic->getAuthor()) !== null) {
                $return['authorUsername'] = $author->username;
            }

            $return['dateCreated'] = DateTimeHelper::toIso8601($characteristic->dateCreated);
            $return['dateUpdated'] = DateTimeHelper::toIso8601($characteristic->dateUpdated);

            return $this->asJson($return);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry saved.'));

        return $this->redirectToPostedUrl($characteristic);
    }

    /**
     * Duplicates an entry.
     *
     * @return Response|null
     * @throws ServerErrorHttpException if reasons
     * @since 3.2.3
     */
    public function actionDuplicateEntry()
    {
        return $this->runAction('save-entry', ['duplicate' => true]);
    }

    /**
     * Deletes an entry.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry cannot be found
     */
    public function actionDeleteEntry()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $entryId = $request->getRequiredBodyParam('entryId');
        $siteId = $request->getBodyParam('siteId');
        $entry = Craft::$app->getEntries()->getEntryById($entryId, $siteId);

        if (!$entry) {
            throw new NotFoundHttpException('Entry not found');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($entry->authorId == $currentUser->id) {
            $this->requirePermission('deleteEntries:' . $entry->getSection()->uid);
        } else {
            $this->requirePermission('deletePeerEntries:' . $entry->getSection()->uid);
        }

        if (!Craft::$app->getElements()->deleteElement($entry)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete entry.'));

            // Send the entry back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entry' => $entry
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Entry deleted.'));

        return $this->redirectToPostedUrl($entry);
    }

    // Private Methods
    // =========================================================================

    /**
     * Preps entry edit variables.
     *
     * @param array &$variables
     * @return Response|null
     * @throws NotFoundHttpException if the requested section or entry cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     */
    private function _prepEditCharacteristicVariables(array &$variables)
    {
        // Get the section
        // ---------------------------------------------------------------------

        if (!empty($variables['groupHandle'])) {
            $variables['group'] = Plugin::$plugin->characteristicGroups->getGroupByHandle($variables['groupHandle']);
        } else if (!empty($variables['groupId'])) {
            $variables['group'] = Plugin::$plugin->characteristicGroups->getGroupById($variables['groupId']);
        }

        if (empty($variables['group'])) {
            throw new NotFoundHttpException('Group not found');
        }

        // Get the characteristic
        // ---------------------------------------------------------------------

        if (empty($variables['characteristic'])) {
            if (!empty($variables['characteristicId'])) {
                $variables['characteristic'] = Craft::$app->getCategories()->getCategoryById($variables['categoryId'], $site->id);

                if (!$variables['characteristic']) {
                    throw new NotFoundHttpException('Characteristic not found');
                }
            } else {
                $variables['characteristic'] = new Characteristic();
                $variables['characteristic']->groupId = $variables['group']->id;
            }
        }
        return null;
    }

    /**
     * Fetches or creates an Entry.
     *
     * @return Entry
     * @throws NotFoundHttpException if the requested entry cannot be found
     */
    private function _getCharacteristicModel(): Characteristic
    {
        $request = Craft::$app->getRequest();
        $characteristicId = $request->getBodyParam('characteristicId');

        if ($characteristicId) {
            $characteristic = null;
//            $characteristic = Craft::$app->getEntries()->getEntryById($characteristicId);

            if (!$characteristic) {
                throw new NotFoundHttpException('Characteristic not found');
            }
        } else {
            $characteristic = new Characteristic();
            $characteristic->groupId = $request->getRequiredBodyParam('groupId');
        }

        return $characteristic;
    }

    /**
     * Populates an Entry with post data.
     *
     * @param Entry $characteristic
     */
    private function _populateCharacteristicModel(Characteristic $characteristic)
    {
        $request = Craft::$app->getRequest();

        $characteristic->handle = $request->getBodyParam('handle', $characteristic->handle);
        $characteristic->title = $request->getBodyParam('title', $characteristic->title);
    }
}
