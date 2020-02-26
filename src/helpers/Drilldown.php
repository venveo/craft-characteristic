<?php

namespace venveo\characteristic\helpers;

use Craft;
use craft\base\Component;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use venveo\characteristic\Characteristic;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicLinkBlock;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\errors\CharacteristicGroupNotFoundException;
use venveo\characteristic\models\CharacteristicGroup;

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

    private $_group;

    private $_currentCharacteristic = null;

    /**
     * @throws CharacteristicGroupNotFoundException
     */
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

        if (!$characteristic) {
            return null;
        }
        $ids = $this->getResults()->ids();
        $valueIds = CharacteristicValue::find()
            ->relatedTo([
                'and',
                ['sourceElement' => $ids],
            ])
            ->characteristicId($characteristic->id)->ids();

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

        // Get the entire possible result set IDs
        $ids = $this->query->ids();

        // Get all link blocks that belong to the result set
        $linksQuery = CharacteristicLinkBlock::find()
            ->ownerId($ids);

        // Get just the available characteristics in the result set
        $linksQuery->indexBy('characteristicId');

        // Make sure we don't include characteristics we've already satisfied
        $skipIds = array_keys($this->state->satisfiedAttributes);
        $parsedSkipIds = array_map(function ($skipId) {
            return '!=' . $skipId;
        }, $skipIds);

        array_unshift($parsedSkipIds, 'and');
        $linksQuery->characteristicId($parsedSkipIds);

        $blocks = $linksQuery->asArray()->all();
        $characteristicIds = ArrayHelper::getColumn($blocks, 'characteristicId');

        $characteristic = $this->_currentCharacteristic = CharacteristicElement::find()->groupId($this->_group->id)->id($characteristicIds)->orderBy('lft ASC')->one();

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