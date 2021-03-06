<?php

namespace venveo\characteristic\helpers;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use venveo\characteristic\elements\Characteristic;
use venveo\characteristic\elements\CharacteristicValue;

/**
 * @property string $url
 */
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
        return UrlHelper::siteUrl(UrlHelper::urlWithParams(Craft::$app->request->fullPath, array_merge(Craft::$app->request->getQueryParams(), ['state' => $this->__toString()])));
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
        $targets = [];
        if (count($this->values) > 1) {
            $targets[] = 'and';
        }
        foreach ($this->values as $value) {
            $targets[] = ['targetElement' => $value];
        }
        $elementQuery->relatedTo($targets);
        return $elementQuery;
    }
}