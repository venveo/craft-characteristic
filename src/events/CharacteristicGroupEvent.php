<?php

namespace venveo\characteristic\events;

use venveo\characteristic\models\CharacteristicGroup;
use yii\base\Event;


class CharacteristicGroupEvent extends Event
{
    // Properties
    // =========================================================================

    /** @var CharacteristicGroup $group */
    public $group;

    /**
     * @var bool Whether the group is brand new
     */
    public $isNew = false;
}
