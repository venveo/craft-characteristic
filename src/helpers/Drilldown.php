<?php

namespace venveo\characteristic\helpers;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use venveo\characteristic\Characteristic;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\errors\CharacteristicGroupNotFoundException;
use venveo\characteristic\models\CharacteristicGroup;
use venveo\characteristic\records\CharacteristicLink;

/**
 *
 * @property mixed $currentOptions
 * @property CharacteristicElement $currentCharacteristic
 * @property mixed $results
 * @property mixed $skipUrl
 */
class Drilldown extends Component
{
    /** @var DrilldownState $state */
    public $state = null;

    /** @var CharacteristicGroup $group */
    public $group = null;

    /** @var ElementQueryInterface $query */
    public $query = null;

    /**
     * @var bool $respectStructure
     * If true, we'll show characteristics in the order explicitly as it appears int he structure
     */
    public $respectStructure = false;

    private $_group;

    private $_currentCharacteristic = null;

    public function init()
    {
        parent::init();

        $group = Characteristic::$plugin->characteristicGroups->getGroupByHandle($this->group);
        if (!$group) {
            throw new CharacteristicGroupNotFoundException('Characteristic group does not exist');
        }
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
//            ->addSelect(['COUNT(ownerId) as score', 'valueId'])
            ->addSelect(['valueId'])
            ->where(['in', 'ownerId', $ids])
            ->andWhere(['characteristicId' => $characteristic->id])
            ->groupBy('valueId')
            ->indexBy('valueId')
            ->asArray()
            ->all());

        // We need to mix in our idempotent values which aren't directly related
        $idempotentIds = CharacteristicValue::find()->characteristicId($characteristic->id)->idempotent(true)->ids();
        $values = CharacteristicValue::find()->id(array_merge($valueIds, $idempotentIds))->orderBy('sortOrder ASC');

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
            ->innerJoin('{{%elements}} elements1', '[[elements1.id]] = [[link.characteristicId]]')
            ->innerJoin('{{%elements}} elements2', '[[elements2.id]] = [[link.valueId]]')
            ->innerJoin('{{%structureelements}} structure', '[[structure.elementId]] = [[elements1.id]]')
            ->addSelect(['COUNT(characteristicId) as score', 'characteristicId', 'structure.lft lft'])
            ->where(['in', 'link.ownerId', $ids])
            ->groupBy(['characteristicId', 'lft']);
        if ($this->respectStructure) {
            $linksQuery->orderBy('lft ASC');
        } else {
            $linksQuery->orderBy('score ASC, lft ASC');
        }

        $linksQuery->indexBy('characteristicId');

        $skipIds = array_keys($this->state->satisfiedAttributes);
        $linksQuery->andWhere(['NOT IN', 'characteristicId', $skipIds]);
        $linksQuery->andWhere(['elements1.dateDeleted' => null, 'elements2.dateDeleted' => null]);

        $links = array_keys($linksQuery->asArray()->all());

        $characteristic = $this->_currentCharacteristic = CharacteristicElement::find()->groupId($this->_group->id)->id($links)->fixedOrder(true)->one();

        return $characteristic;
    }

    public function getResults()
    {
        return $this->state->modifyQuery($this->query);
    }

    public function getState(): DrilldownState
    {
        return $this->state;
    }

    public function getSkipUrl()
    {
        $localState = clone $this->state;
        return $localState->setCharacteristicSatisfied($this->getCurrentCharacteristic())->getUrl();
    }
}