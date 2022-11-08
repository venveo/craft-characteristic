<?php
namespace venveo\characteristic\assetbundles\characteristicasset;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CharacteristicAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@venveo/characteristic/web/assets/dist';

        $this->depends = [
            CpAsset::class,
        ];

        parent::init();
    }
}