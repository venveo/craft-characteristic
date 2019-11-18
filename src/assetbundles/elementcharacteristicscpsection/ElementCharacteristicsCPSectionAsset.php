<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\assetbundles\elementcharacteristicscpsection;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class ElementCharacteristicsCPSectionAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@venveo/characteristic/assetbundles/elementcharacteristicscpsection/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/ElementCharacteristics.js',
        ];

        $this->css = [
            'css/ElementCharacteristics.css',
        ];

        parent::init();
    }
}
