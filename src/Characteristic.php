<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpSettingsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\UserPermissions;
use craft\web\twig\variables\Cp;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use nystudio107\pluginvite\services\VitePluginService;
use venveo\characteristic\assetbundles\characteristicasset\CharacteristicAsset;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicLinkBlock as CharacteristicLinkBlockElement;
use venveo\characteristic\elements\CharacteristicValue as CharacteristicValueElement;
use venveo\characteristic\fields\Characteristics as CharacteristicsField;
use venveo\characteristic\services\CharacteristicGroups;
use venveo\characteristic\services\CharacteristicLinkBlocks;
use venveo\characteristic\services\Characteristics;
use venveo\characteristic\services\CharacteristicValues;
use venveo\characteristic\variables\CharacteristicVariable;
use yii\base\Event;

/**
 * Class Characteristic
 *
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 * @property  CharacteristicGroups $characteristicGroups
 * @property  Characteristics $characteristics
 * @property  CharacteristicValues $characteristicValues
 * @property  CharacteristicLinkBlocks $characteristicLinkBlocks
 */
class Characteristic extends Plugin
{
    const PERMISSION_EDIT_GROUP = 'editCharacteristicGroup';

    /**
     * @var Characteristic
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    public $hasCpSettings = true;
    public $hasCpSection = true;

    /**
     * @var string
     */
    // 1.0.0.2 = 1.0.0-beta.11
    public $schemaVersion = '1.0.0.2';

    public function __construct($id, $parent = null, array $config = [])
    {
        $config['components'] = [
            'characteristicGroups' => CharacteristicGroups::class,
            'characteristics' => Characteristics::class,
            'characteristicValues' => CharacteristicValues::class,
            'characteristicLinkBlocks' => CharacteristicLinkBlocks::class,
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => CharacteristicAsset::class,
                'useDevServer' => true,
                'devServerPublic' => 'http://localhost:3001',
                'serverPublic' => 'http://localhost:8000',
                'errorEntry' => '/src/js/app.ts',
                'devServerInternal' => 'http://craft-characteristic-buildchain:3001',
                'checkDevServer' => true,
            ],
        ];
        parent::__construct($id, $parent, $config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->_registerCpRoutes();
        $this->_registerProjectConfig();
        $this->_registerElementTypes();
        $this->_registerFieldTypes();
        $this->_registerVariables();
        $this->_registerPermissions();

        Event::on(Cp::class, Cp::EVENT_REGISTER_CP_SETTINGS,
            function (RegisterCpSettingsEvent $e) {
                $e->settings['Content']['characteristics'] = [
                    'icon' => $this->cpNavIconPath(),
                    'url' => 'settings/characteristics',
                    'label' => 'Characteristics'
                ];
            });
    }

    public function getSettingsResponse()
    {
        return Craft::$app->response->redirect(UrlHelper::cpUrl('settings/characteristics'));
    }

    public function getCpNavItem()
    {
        if (count(static::$plugin->characteristicGroups->getAllGroups())) {
            $navItem = parent::getCpNavItem();
            $navItem['label'] = 'Characteristics';
            $navItem['url'] = UrlHelper::cpUrl('characteristics');
            return $navItem;
        }
        return null;
    }

    private function _registerCpRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['settings/characteristics'] = 'characteristic/settings/index';
                $event->rules['settings/characteristics/new'] = 'characteristic/settings/edit-group';
                $event->rules['settings/characteristics/<groupId:\d+>'] = 'characteristic/settings/edit-group';

                $event->rules['characteristics'] = 'characteristic/characteristics/index';
                $event->rules['characteristics/<groupHandle:{handle}>'] = 'characteristic/characteristics/index';
                $event->rules['characteristics/save-characteristic'] = 'characteristic/characteristics/save-characteristic';
                $event->rules['characteristics/<groupHandle:{handle}>/new'] = 'characteristic/characteristics/edit-characteristic';

                $event->rules['characteristics/<groupHandle:{handle}>/<characteristicId:\d+>'] = 'characteristic/characteristics/edit-characteristic';
                $event->rules['characteristics/<groupHandle:{handle}>/<characteristicId:\d+>/<valueId:\d+>'] = 'characteristic/characteristic-values/edit-value';
                $event->rules['characteristics/<groupHandle:{handle}>/<characteristicId:\d+>/new'] = 'characteristic/characteristic-values/edit-value';
            }
        );
    }

    private function _registerProjectConfig()
    {
        Craft::$app->projectConfig
            ->onAdd('characteristicGroups.{uid}', [$this->characteristicGroups, 'handleChangedGroup'])
            ->onUpdate('characteristicGroups.{uid}', [$this->characteristicGroups, 'handleChangedGroup'])
            ->onRemove('characteristicGroups.{uid}', [$this->characteristicGroups, 'handleDeletedGroup']);
    }

    private function _registerElementTypes()
    {
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CharacteristicElement::class;
                $event->types[] = CharacteristicLinkBlockElement::class;
                $event->types[] = CharacteristicValueElement::class;
            }
        );
    }

    private function _registerFieldTypes()
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CharacteristicsField::class;
            }
        );
    }

    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('characteristic', CharacteristicVariable::class);
            }
        );
    }

    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function (RegisterUserPermissionsEvent $event) {
            $groups = self::getInstance()->characteristicGroups->getAllGroups();
            foreach($groups as $group) {
                $permissions = [];
                $permissions[self::PERMISSION_EDIT_GROUP .':'. $group->uid] = [
                    'label' => Craft::t('characteristic', 'Edit ' . $group->name)
                ];
                $event->permissions[Craft::t('characteristic', 'Characteristic Group - '. $group->name)] = $permissions;
            }
        });
    }
}
