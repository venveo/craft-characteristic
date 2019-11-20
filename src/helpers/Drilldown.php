<?php

namespace venveo\characteristic\helpers;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use venveo\characteristic\Characteristic;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicValue as CharacteristicValueElement;
use venveo\characteristic\models\CharacteristicGroup;
use venveo\characteristic\records\CharacteristicLink;

class Drilldown extends Component
{
    /** @var DrilldownState $state */
    public $state = null;

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

        $state = Craft::$app->request->getParam('state');
        if (!$state) {
            $this->state = new DrilldownState();
        } else {
            $this->state = DrilldownState::fromString($state);
        }
    }

    public function getCurrentOptions()
    {
        $characteristic = $this->getCurrentCharacteristic();
        $ids = $this->getResults()->ids();
        $valueIds = array_keys(CharacteristicLink::find()
            ->addSelect(['COUNT(elementId) as score', 'valueId'])
            ->where(['in', 'elementId', $ids])
            ->andWhere(['characteristicId' => $characteristic->id])
            ->groupBy('valueId')
            ->orderBy('score DESC')
            ->indexBy('valueId')
            ->asArray()
            ->all());

        $values = CharacteristicValueElement::find()->id($valueIds);

        return $values;
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
        $linksQuery = CharacteristicLink::find()
            ->alias('link')
            ->leftJoin('{{%elements}} elements1', '[[elements1.id]] = [[link.characteristicId]]')
            ->leftJoin('{{%elements}} elements2', '[[elements2.id]] = [[link.valueId]]')
            ->addSelect(['COUNT(characteristicId) as score', 'characteristicId'])
            ->where(['in', 'elementId', $ids])

            ->groupBy('characteristicId')
            // TODO: Not sure if this makes sense...
            ->orderBy('score ASC')
            ->indexBy('characteristicId');

        $skipIds = array_keys($this->state->satisfiedAttributes);
        $linksQuery->andWhere(['NOT IN', 'characteristicId', $skipIds]);
        $linksQuery->andWhere([ 'elements1.dateDeleted' => null, 'elements2.dateDeleted' => null]);

        $links = array_keys($linksQuery->asArray()->all());

        $characteristic = $this->_currentCharacteristic = CharacteristicElement::find()->groupId($this->_group->id)->id($links)->fixedOrder(true)->one();

        return $characteristic;
    }

    public function getResults()
    {
        return $this->state->modifyQuery($this->query);
    }

    public function getState()
    {
        return $this->state;
    }

    public function getSkipUrl() {
        $localState = clone $this->state;
        return $localState->setCharacteristicSatisfied($this->getCurrentCharacteristic())->getUrl();
    }
}