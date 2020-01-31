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
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\AdminTable;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\records\EntryType;
use craft\records\Field;
use craft\records\FieldLayout;
use craft\records\FieldLayoutField;
use craft\web\Controller;
use Throwable;
use venveo\characteristic\assetbundles\characteristicelement\CharacteristicElement;
use venveo\characteristic\Characteristic as Plugin;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\fields\Characteristics as CharacteristicsField;
use venveo\characteristic\models\CharacteristicGroup;
use yii\base\Exception;
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
     * Called when a user beings up a characteristic for editing before being displayed.
     *
     * @param string $groupHandle
     * @param int|null $characteristicId
     * @param Characteristic $characteristic The characteristic being edited, if there were any validation errors.
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
        /** @var CharacteristicGroup $group */
        $group = $variables['group'];

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

        $tabs = [];
        if ($characteristicId) {
            $tabs['overview'] = [
                'label' => Craft::t('characteristic', 'Overview'),
                'url' => '#overview',
            ];
        }

        if ($characteristic->getFieldLayout()->getTabs()) {
            $tabs['characteristicFields'] = [
                'label' => Craft::t('characteristic', 'Content'),
                'url' => '#fields',
            ];
        }

        if ($characteristicId) {
            $tabs['audit'] = [
                'label' => Craft::t('characteristic', 'Audit'),
                'url' => '#audit',
            ];
        }

        $variables['tabs'] = $tabs;
        $variables['selectedTab'] = isset($characteristicId) ? 'overview' : 'characteristicFields';


        // Render the template!
        return $this->renderTemplate('characteristic/characteristics/_edit', $variables);
    }

    /**
     * Preps characteristic edit variables.
     *
     * @param array &$variables
     * @return Response|null
     * @throws NotFoundHttpException if the requested group or characteristic cannot be found
     * @throws ForbiddenHttpException if the user is not permitted to edit content in the requested site
     */
    private function _prepEditCharacteristicVariables(array &$variables)
    {
        // Get the characteristic
        // ---------------------------------------------------------------------

        if (!empty($variables['groupHandle'])) {
            $variables['group'] = Plugin::$plugin->characteristicGroups->getGroupByHandle($variables['groupHandle']);
        } else if (!empty($variables['groupId'])) {
            $variables['group'] = Plugin::$plugin->characteristicGroups->getGroupById($variables['groupId']);
        }

        /** @var CharacteristicGroup $group */
        $group = $variables['group'];

        if (empty($variables['group'])) {
            throw new NotFoundHttpException('Group not found');
        }

        // Get the characteristic
        // ---------------------------------------------------------------------

        if (empty($variables['characteristic'])) {
            if (!empty($variables['characteristicId'])) {

                $variables['characteristic'] = Plugin::$plugin->characteristics->getCharacteristicById($variables['characteristicId']);

                if (!$variables['characteristic']) {
                    throw new NotFoundHttpException('Characteristic not found');
                }
            } else {
                $newCharacteristic = new Characteristic();
                $newCharacteristic->groupId = $group->id;
                $newCharacteristic->required = $group->requiredByDefault;
                $newCharacteristic->allowCustomOptions = $group->allowCustomOptionsByDefault;
                $newCharacteristic->maxValues = 1;
                $variables['characteristic'] = $newCharacteristic;
            }
        }

        // Set the base CP edit URL
        $variables['baseCpEditUrl'] = "characteristics/{$group->handle}/{id}";

        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'];

        // Set the "Save and add another" URL
        $variables['nextCharacteristicUrl'] = "characteristics/{$group->handle}/new";
        return null;
    }

    /**
     * Saves a characteristic.
     *
     * @param bool $duplicate Whether the characteristic should be duplicated
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     * @throws NotFoundHttpException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function actionSaveCharacteristic(bool $duplicate = false)
    {
        $this->requirePostRequest();

        $characteristic = $this->_getCharacteristicModel();
        $request = Craft::$app->getRequest();

        $this->_populateCharacteristicModel($characteristic);


        if (!Craft::$app->getElements()->saveElement($characteristic)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $characteristic->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('characteristic', 'Couldn’t save characteristic.'));

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

            $return['dateCreated'] = DateTimeHelper::toIso8601($characteristic->dateCreated);
            $return['dateUpdated'] = DateTimeHelper::toIso8601($characteristic->dateUpdated);

            return $this->asJson($return);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Characteristic saved.'));

        return $this->redirectToPostedUrl($characteristic);
    }

    /**
     * Fetches or creates an characteristic.
     *
     * @return Characteristic
     * @throws NotFoundHttpException if the requested characteristic cannot be found
     */
    private function _getCharacteristicModel(): Characteristic
    {
        $request = Craft::$app->getRequest();
        $characteristicId = $request->getBodyParam('characteristicId');

        if ($characteristicId) {
            $characteristic = Plugin::$plugin->characteristics->getCharacteristicById($characteristicId);
            if (!$characteristic) {
                throw new NotFoundHttpException('Characteristic not found');
            }
        } else {
            $characteristic = new Characteristic();
            $characteristic->groupId = $request->getRequiredBodyParam('groupId');
        }

        return $characteristic;
    }

    // Private Methods
    // =========================================================================

    /**
     * Populates an characteristic with post data.
     *
     * @param Characteristic $characteristic
     */
    private function _populateCharacteristicModel(Characteristic $characteristic)
    {
        $request = Craft::$app->getRequest();

        $characteristic->handle = $request->getBodyParam('handle', $characteristic->handle);
        $characteristic->title = $request->getBodyParam('title', $characteristic->title);
        $characteristic->allowCustomOptions = $request->getBodyParam('allowCustomOptions', $characteristic->allowCustomOptions);
        $characteristic->required = $request->getBodyParam('required', $characteristic->required);
        $characteristic->maxValues = $request->getBodyParam('maxValues', $characteristic->maxValues);
        $characteristic->setFieldValuesFromRequest('fields');
    }

    /**
     * Duplicates an characteristic.
     *
     * @return Response|null
     * @throws ServerErrorHttpException if reasons
     * @since 3.2.3
     */
    public function actionDuplicateCharacteristic()
    {
        return $this->runAction('save-characteristic', ['duplicate' => true]);
    }

    /**
     * Deletes a characteristic.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested characteristic cannot be found
     * @throws Throwable
     * @throws MissingComponentException
     * @throws BadRequestHttpException
     */
    public function actionDeleteCharacteristic()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $characteristicId = $request->getRequiredBodyParam('characteristicId');
        $characteristic = Plugin::$plugin->characteristics->getCharacteristicById($characteristicId);

        if (!$characteristic) {
            throw new NotFoundHttpException('Characteristic not found');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!Craft::$app->getElements()->deleteElement($characteristic)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('characteristic', 'Couldn’t delete characteristic.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'characteristic' => $characteristic
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('characteristic', 'Characteristic deleted.'));

        return $this->redirectToPostedUrl($characteristic);
    }

    public function actionAuditTable()
    {
//        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $page = $request->getParam('page', 1);
        $characteristicId = $request->getRequiredParam('characteristicId');
        $sort = $request->getParam('sort', null);
        $limit = $request->getParam('per_page', 10);
        $search = $request->getParam('search', null);
        $siteId = $request->getParam('siteId', Craft::$app->sites->getCurrentSite()->id);
        $offset = ($page - 1) * $limit;

        $characteristic = \venveo\characteristic\Characteristic::getInstance()->characteristics->getCharacteristicById($characteristicId);
        if (!$characteristic) {
            return $this->asErrorJson('Characteristic not found');
        }

        // First we'll get all the fields that can use this characteristic
        $fieldRecords = Field::find()->where(['=', 'type', CharacteristicsField::class])->all();
        $targetFields = [];
        foreach($fieldRecords as $fieldRecord) {
            /** @var CharacteristicsField $field */
            $field = Craft::$app->fields->getFieldById($fieldRecord->id);
            $group = $field->getCharacteristicGroup();
            if ((int)$group->id === (int)$characteristic->groupId) {
                $targetFields[] = $field;
            }
        }

        $fieldIds = ArrayHelper::getColumn($targetFields, 'id', false);

        // Now let's get all the field layouts with those fields
        $fieldLayoutIds = FieldLayoutField::find()->select(['layoutId'])->distinct(true)->where(['in', 'fieldId', $fieldIds])->column();

        // All of the entry types with these fields
        $entryTypeIds = EntryType::find()->select(['id'])->where(['in', 'fieldLayoutId', $fieldLayoutIds])->column();


//        $recordQuery = CatchAllUrl::find();

//        if ($search) {
//            $likeOperator = Craft::$app->getDb()->getIsPgsql() ? 'ILIKE' : 'LIKE';
//            $recordQuery->andWhere([
//                'or',
//                [$likeOperator, '[[id]]', $search],
//                [$likeOperator, '[[uri]]', $search],
//                [$likeOperator, '[[uid]]', $search],
//                [$likeOperator, '[[query]]', $search],
//                [$likeOperator, '[[referrer]]', $search],
//                [$likeOperator, '[[dateUpdated]]', $search],
//                [$likeOperator, '[[dateCreated]]', $search]
//            ]);
//        }

//        if ($siteId) {
//            $recordQuery->andWhere(['=', '[[siteId]]', $siteId]);
//        }

//        $recordQuery->andWhere(['=', '[[ignored]]', (bool)$ignoredOnly]);

//        if ($sort) {
//            $sortData = explode('|', $sort);
//            $sortKey = $sortData[0];
//            $sortDir = $sortData[1] === 'asc' ? SORT_ASC : SORT_DESC;
//            $orderParam = [$sortKey => $sortDir];
//            $recordQuery->orderBy($orderParam);
//        } else {
//            $recordQuery->orderBy(['dateCreated' => SORT_DESC]);
//        }

//        $total = $recordQuery->count();

//        $recordQuery->offset($offset);
//        $recordQuery->limit($limit);

//        $registered404s = $recordQuery->all();

//        $rows = [];
//        foreach ($registered404s as $item) {
//            $title = $item['uri'];
//            if (isset($item['query']) && $item['query']) {
//                $title .= '?' . $item['query'];
//            }
//
//            $rows[] = [
//                'id' => $item['id'],
//                'siteId' => $item['siteId'],
//                'ignored' => $item['ignored'],
//                'title' => Html::encode($title),
//            ];
//        }

//        return $this->asJson([
//            'pagination' => AdminTable::paginationLinks($page, $total, $limit),
//            'data' => $rows,
//        ]);
    }
}
