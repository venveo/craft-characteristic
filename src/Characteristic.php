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
use craft\base\Element;
use craft\base\Plugin;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpSettingsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields;
use craft\web\twig\variables\Cp;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use venveo\characteristic\behaviors\ElementCharacteristicsBehavior;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicValue as CharacteristicValueElement;
use venveo\characteristic\fields\Characteristics as CharacteristicsField;
use venveo\characteristic\services\CharacteristicGroups;
use venveo\characteristic\services\CharacteristicLinks;
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
 * @property  CharacteristicLinks $characteristicLinks
 */
class Characteristic extends Plugin
{
    // Static Properties
    // =========================================================================

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
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;
        $this->setComponents([
            'characteristicGroups' => CharacteristicGroups::class,
            'characteristics' => Characteristics::class,
            'characteristicValues' => CharacteristicValues::class,
            'characteristicLinks' => CharacteristicLinks::class,
        ]);

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


        Craft::$app->projectConfig
            ->onAdd('characteristicGroups.{uid}', [$this->characteristicGroups, 'handleChangedGroup'])
            ->onUpdate('characteristicGroups.{uid}', [$this->characteristicGroups, 'handleChangedGroup'])
            ->onRemove('characteristicGroups.{uid}', [$this->characteristicGroups, 'handleDeletedGroup']);

        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CharacteristicElement::class;
                $event->types[] = CharacteristicValueElement::class;
            }
        );

        Event::on(Element::class, Element::EVENT_DEFINE_BEHAVIORS, function (DefineBehaviorsEvent $e) {
            $e->behaviors[] = ElementCharacteristicsBehavior::class;
        });

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = CharacteristicsField::class;
            }
        );

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('characteristic', CharacteristicVariable::class);
            }
        );

        Event::on(Cp::class, Cp::EVENT_REGISTER_CP_SETTINGS,
            function (RegisterCpSettingsEvent $e) {
                $e->settings['Content']['characteristics'] = [
                    'icon' => '@app/icons/sliders.svg',
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
        if(count(static::$plugin->characteristicGroups->getAllGroups())) {
            return [
                'label' => 'Characteristics',
                'url' => UrlHelper::cpUrl('characteristics'),
            ];
        }
        return null;
    }
    // Protected Methods
    // =========================================================================
}
