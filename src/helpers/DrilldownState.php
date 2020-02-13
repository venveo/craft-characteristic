<?php

namespace venveo\characteristic\helpers;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;

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
        return UrlHelper::urlWithParams(Craft::$app->request->fullPath, array_merge(Craft::$app->request->getQueryParams(), ['state' => $this->__toString()]));
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
        // TODO: Merge in existing relations
        $elementQuery->relatedTo([
            'targetElement' => $this->values
        ]);
        return $elementQuery;
    }
}