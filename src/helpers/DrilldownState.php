<?php

namespace venveo\characteristic\helpers;

use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use venveo\characteristic\elements\Characteristic;
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

    public function setCharacteristicSatisfied(Characteristic $characteristic)
    {
        $this->satisfiedAttributes[$characteristic->id] = true;
        return $this;
    }

    public function getUrl()
    {
        return UrlHelper::urlWithParams(UrlHelper::baseRequestUrl(), ['state' => $this->__toString()]);
    }

    public function __toString()
    {
        return base64_encode(Json::encode($this->getAttributes()));
    }

    public function modifyQuery(ElementQueryInterface $elementQuery)
    {
        if (!count($this->values)) {
            return $elementQuery;
        }
        $ids = $elementQuery->ids();
        $query = CharacteristicLink::find();
        $query->select(['ownerId', 'count(ownerId) as total']);
        foreach ($this->values as $characteristicId => $valueId) {
            $query->orWhere(['characteristicId' => $characteristicId, 'valueId' => $valueId]);
        }
        $query->groupBy(['ownerId']);
        $query->having(['>=', 'total', count($this->values)]);
        $query->andWhere(['IN', 'ownerId', $ids]);
        $query->indexBy('ownerId');
        $query->asArray();

        $validIds = array_keys($query->all());
        $elementQuery->andWhere(['IN', '[[elements.id]]', $validIds]);
        return $elementQuery;
    }
}