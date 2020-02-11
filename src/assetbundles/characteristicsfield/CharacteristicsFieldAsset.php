<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2020 Venveo
 */

namespace venveo\characteristic\assetbundles\characteristicsfield;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for admin tables
 */
class CharacteristicsFieldAsset extends AssetBundle
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist/';

        $this->depends = [
            CpAsset::class
        ];

        $this->css = [
            'CharacteristicsInput.css',
        ];

        $this->js = [
            'CharacteristicsInput.js',
        ];

        parent::init();
    }
}
