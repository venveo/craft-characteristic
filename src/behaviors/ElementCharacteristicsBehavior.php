<?php

namespace venveo\characteristic\behaviors;

use craft\base\Element;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\records\CharacteristicLink;
use yii\base\Behavior;

class ElementCharacteristicsBehavior extends Behavior
{
    public function characteristicLinks($characteristic = null)
    {
        /** @var Element $element */
        $element = $this->owner;
        $linksQuery = CharacteristicLink::find()->where(['elementId' => $element->id]);
        if ($characteristic) {
            if($characteristic instanceof Characteristic) {
                $characteristicId = $characteristic->id;
            } else {
                $characteristicId = $characteristic;
            }

            $linksQuery->andWhere(['characteristicId' => $characteristicId]);
        }
        $linksQuery->with(['characteristicValue']);
        return $linksQuery->all();
    }
}