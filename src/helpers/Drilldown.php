<?php

namespace venveo\characteristic\helpers;

use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use venveo\characteristic\Characteristic;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicValue as CharacteristicValueElement;
use venveo\characteristic\models\CharacteristicGroup;
use venveo\characteristic\records\CharacteristicLink;

class Drilldown extends Component
{
    public $state = [];

    /** @var CharacteristicGroup $group */
    public $group = null;

    /** @var ElementQueryInterface $query */
    public $query = null;

    private $_group;

    private $_currentCharacteristic = null;

    public function init()
    {
        parent::init();

        $group = Characteristic::$plugin->characteristicGroups->getGroupByHandle($this->group);
        $this->_group = $group;

        $state = \Craft::$app->request->getParam('state');
        if (!$state) {
            $this->state = [];
        } else {
            $this->state = Json::decode(base64_decode($state));
        }
    }

    /**
     * @return CharacteristicElement
     */
    public function getCurrentCharacteristic()
    {
        if ($this->_currentCharacteristic instanceof CharacteristicElement) {
            return $this->_currentCharacteristic;
        }
        $ids = $this->query->ids();
        $links = array_keys(CharacteristicLink::find()
            ->addSelect(['COUNT(characteristicId) as score', 'characteristicId'])
            ->where(['in', 'elementId', $ids])
            ->groupBy('characteristicId')
            ->orderBy('score DESC')
            ->indexBy('characteristicId')
            ->asArray()
            ->all());

        $characteristic = $this->_currentCharacteristic = CharacteristicElement::find()->groupId($this->_group->id)->id($links)->fixedOrder(true)->one();

        return $characteristic;
    }

    public function getCurrentOptions() {
        $characteristic = $this->getCurrentCharacteristic();
        $ids = $this->query->ids();

        $valueIds = array_keys(CharacteristicLink::find()
            ->addSelect(['COUNT(elementId) as score', 'valueId'])
            ->where(['in', 'elementId', $ids])
            ->andWhere(['characteristicId' => $characteristic->id])
            ->groupBy('valueId')
            ->orderBy('score DESC')
            ->indexBy('valueId')
            ->asArray()
            ->all());

        $values = CharacteristicValueElement::find()->id($valueIds)->fixedOrder(true);

        return $values;
    }

    public function getResults() {
        return $this->query;
    }
}