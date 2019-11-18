<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\assetbundles\characteristicsfield;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicsFieldAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@venveo/characteristic/assetbundles/characteristicsfield/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Characteristics.js',
        ];

        $this->css = [
            'css/Characteristics.css',
        ];

        parent::init();
    }
}
