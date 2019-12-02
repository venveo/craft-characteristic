<?php

namespace venveo\characteristic\behaviors;

use craft\base\Element;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\records\CharacteristicLink;
use yii\base\Behavior;

class ElementCharacteristicsBehavior extends Behavior
{
//    public function characteristicLinks($characteristic = null)
//    {
//        /** @var Element $element */
//        $element = $this->owner;
//        $linksQuery = CharacteristicLink::find()->where(['elementId' => $element->id]);
//        if ($characteristic instanceof Characteristic) {
//            $linksQuery->andWhere(['characteristicId' => $characteristic->id]);
//        }
//        $linksQuery
//            ->alias('link')
//            ->leftJoin('{{%elements}} elements1', '[[elements1.id]] = [[link.characteristicId]]')
//            ->leftJoin('{{%elements}} elements2', '[[elements2.id]] = [[link.valueId]]');
//        $linksQuery->andWhere(['elements1.dateDeleted' => null, 'elements2.dateDeleted' => null]);
//
//        if ($characteristic) {
//            if ($characteristic instanceof Characteristic) {
//                $characteristicId = $characteristic->id;
//            } else {
//                $characteristicId = $characteristic;
//            }
//            $linksQuery->andWhere(['characteristicId' => $characteristicId]);
//        }
//        $linksQuery->with(['characteristicValue']);
//        return $linksQuery->all();
//    }

    public function characteristics()
    {
        /** @var Element $element */
        $element = $this->owner;
        return Characteristic::find()->relatedTo(['sourceElement' => $element]);
    }
}