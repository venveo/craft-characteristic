<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\assetbundles\characteristicelement;

use Craft;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;
use venveo\characteristic\Characteristic;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicElement extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@venveo/characteristic/assetbundles/characteristicelement/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'CharacteristicIndex.js',
        ];

        $this->css = [
        ];

        // Define the Craft object
        $craftJson = Json::encode($this->_data(), JSON_UNESCAPED_UNICODE);
        $js = <<<JS
window.Characteristic = {$craftJson};
JS;
        Craft::$app->view->registerJs($js, View::POS_HEAD);

        parent::init();
    }

    private function _data()
    {
        return [
            'editableCharacteristicGroups' => Characteristic::$plugin->characteristicGroups->getEditableGroups()
        ];
    }
}
