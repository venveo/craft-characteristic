<?php

namespace venveo\characteristic\helpers;

use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\records\CharacteristicLink;

class DrilldownState extends Component
{
    public $values = [];

    public $satisfiedAttributes = [];

    public static function fromString(string $state)
    {
        return new static(Json::decode(base64_decode($state)));
    }

    public function setCharacteristicValue(CharacteristicValue $characteristicValue)
    {
        $characteristicId = $characteristicValue->characteristicId;
        $this->values[$characteristicId] = $characteristicValue->id;
        $this->satisfiedAttributes[$characteristicId] = true;
        return $this;
    }

    public function __toString()
    {
        return base64_encode(Json::encode($this->getAttributes()));
    }

    public function getUrl()
    {
        return UrlHelper::urlWithParams(UrlHelper::baseRequestUrl(), ['state' => $this->__toString()]);
    }

    public function modifyQuery(ElementQueryInterface $elementQuery)
    {
        if (!count($this->satisfiedAttributes)) {
            return $elementQuery;
        }
        $ids = $elementQuery->ids();
        $query = CharacteristicLink::find();
        $query->select(['elementId', 'count(elementId) as total']);

//        $subquery = CharacteristicLink::find();
//        $subquery->select(['id', 'characteristicId', 'valueId']);
        foreach ($this->values as $characteristicId => $valueId) {
            $query->orWhere(['characteristicId' => $characteristicId, 'valueId' => $valueId]);
        }
//        $subquery->andWhere(['IN', 'elementId', $ids]);
        $query->groupBy(['elementId']);
        $query->having(['>=', 'total', count($this->values)]);


        $query->andWhere(['IN', 'elementId', $ids]);
//        $query->andWhere(['IN', 'id', $subquery]);
        $query->indexBy('elementId');
        $query->asArray();

//        \Craft::dd($query->all());

        $validIds = array_keys($query->all());
        $elementQuery->andWhere(['IN', '[[elements.id]]', $validIds]);
        return $elementQuery;
    }
}