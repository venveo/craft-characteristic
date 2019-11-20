<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace venveo\characteristic\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use venveo\characteristic\Characteristic;
use venveo\characteristic\models\CharacteristicGroup;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All section actions require an admin
        $this->requireAdmin();

        parent::init();
    }

    /**
     * Sections index.
     *
     * @param array $variables
     * @return Response The rendering result
     */
    public function actionIndex(array $variables = []): Response
    {
        $variables['groups'] = Characteristic::$plugin->characteristicGroups->getAllGroups();

        return $this->renderTemplate('characteristic/settings/_index', $variables);
    }


    public function actionEditGroup(int $groupId = null, CharacteristicGroup $group = null): Response
    {
        $variables = [
            'groupId' => $groupId,
            'brandNewGroup' => false
        ];

        if ($groupId !== null) {
            if ($group === null) {
                $group = Craft::$app->getSections()->getSectionById($groupId);

                if (!$group) {
                    throw new NotFoundHttpException('Group not found');
                }
            }

            $variables['title'] = trim($group->name) ?: Craft::t('app', 'Edit Group');
        } else {
            if ($group === null) {
                $group = new CharacteristicGroup();
                $variables['brandNewGroup'] = true;
            }

            $variables['title'] = Craft::t('characteristic', 'Create a new group');
        }

        $variables['group'] = $group;

        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('app', 'Characteristics'),
                'url' => UrlHelper::url('settings/characteristics')
            ],
        ];

//        Craft::$app->getView()->registerAssetBundle(EditSectionAsset::class);

        return $this->renderTemplate('characteristic/settings/_edit', $variables);
    }

    /**
     * Saves a group.
     *
     * @return Response|null
     * @throws BadRequestHttpException if any invalid site IDs are specified in the request
     */
    public function actionSaveGroup()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $group = new CharacteristicGroup();

        // Main section settings
        $group->id = $request->getBodyParam('groupId');
        $group->name = $request->getBodyParam('name');
        $group->handle = $request->getBodyParam('handle');

        // Save it
        if (!Characteristic::$plugin->characteristicGroups->saveGroup($group)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save group.'));

            // Send the section back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'group' => $group
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('characteristic', 'Group saved.'));

        return $this->redirectToPostedUrl($group);
    }

    /**
     * Deletes a group.
     *
     * @return Response
     */
    public function actionDeleteGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $groupId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Characteristic::$plugin->characteristicGroups->deleteGroupById($groupId);

        return $this->asJson(['success' => true]);
    }
}
