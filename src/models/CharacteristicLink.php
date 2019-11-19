<?php
/**
 *  OAuth 2.0 Client plugin for Craft CMS 3
 *  @link      https://www.venveo.com
 *  @copyright Copyright (c) 2018-2019 Venveo
 */

namespace venveo\characteristic\models;

use craft\base\Model;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use venveo\characteristic\records\CharacteristicGroup as CharacteristicGroupRecord;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicLink extends Model
{
    // Public Properties
    // =========================================================================


    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var string|null Group's UID
     */
    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        return $rules;
    }
}
