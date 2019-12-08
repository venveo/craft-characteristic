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
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;
use Throwable;
use yii\base\Exception;
use yii\caching\TagDependency;
use yii\web\NotFoundHttpException;
use function count;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicsFieldAsset extends AssetBundle
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    private $useDevServer = false;
    /**
     * @var bool
     */
    private $devServerBaseUrl = 'https://localhost:3000/';


    // Public Methods
    // =========================================================================

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
                $this->devServerBaseUrl.'js/app.js',
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
