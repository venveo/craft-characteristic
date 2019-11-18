<?php

namespace venveo\characteristic\errors;
use yii\base\Exception;


class CharacteristicGroupNotFoundException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Characteristic group not found';
    }
}
