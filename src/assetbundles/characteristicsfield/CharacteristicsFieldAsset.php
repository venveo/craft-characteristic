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
use craft\web\assets\vue\VueAsset;

/**
 * Asset bundle for admin tables
 */
class CharacteristicsFieldAsset extends AssetBundle
{
    /**
     * @var bool
     */
    private $useDevServer = true;

    /**
     * @var bool
     */
    private $devServerBaseUrl = 'https://localhost:3000/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist/';

        $this->depends = [
            CpAsset::class,
            VueAsset::class,
        ];

        if ($this->useDevServer) {
            $this->js = [
                $this->devServerBaseUrl . 'app.js',
            ];
        } else {
            $this->css = [
                'css/chunk-vendors.css',
                'css/app.css',
            ];

            $this->js = [
                'js/chunk-vendors.js',
                'js/app.js',
            ];
        }

        parent::init();
    }
}
