<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\fields;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\ElementNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use Exception;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use venveo\characteristic\assetbundles\characteristicsfield\CharacteristicsFieldAsset;
use venveo\characteristic\Characteristic;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicLink;
use venveo\characteristic\elements\db\CharacteristicLinkQuery;
use yii\base\InvalidConfigException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 * @property mixed $settingsHtml
 * @property array $elementValidationRules
 * @property array $sourceOptions
 */
class Characteristics extends Field
{
    // Constants
    // =========================================================================

    const PROPAGATION_METHOD_NONE = 'none';
    const PROPAGATION_METHOD_SITE_GROUP = 'siteGroup';
    const PROPAGATION_METHOD_LANGUAGE = 'language';
    const PROPAGATION_METHOD_ALL = 'all';

    // Static Methods
    // =========================================================================
    /**
     * @var string|null The source key that this field can relate elements from (used if [[allowMultipleSources]] is set to false)
     */
    public $source;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('characteristic', 'Characteristics');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [];
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'source';

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof CharacteristicLinkQuery) {
            return $value;
        }
        $query = CharacteristicLink::find();

        // Existing element?
        /** @var Element|null $element */
        if ($element && $element->id) {
            $query->ownerId($element->id);
        } else {
            $query->id(false);
        }


        $query
            ->fieldId($this->id)
            ->siteId($element->siteId ?? null);


        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        if ($value === '') {
            $query->setCachedResult([]);
        } else if ($element && is_array($value)) {
            $query->setCachedResult($this->_createLinksFromSerializedData($value, $element));
        }

        return $query;
    }

    /**
     * Creates an array of blocks based on the given serialized data.
     *
     * @param array $value The raw field value
     * @param ElementInterface $element The element the field is associated with
     * @return CharacteristicLink[]
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws \yii\base\Exception
     */
    private function _createLinksFromSerializedData(array $value, ElementInterface $element): array
    {
        $source = ElementHelper::findSource(CharacteristicElement::class, $this->source, 'index');
        $groupId = $source['criteria']['groupId'];

        /** @var Element $element */
        // Get the possible block types for this field
        /** @var Characteristic[] $characteristics */
        $characteristics = ArrayHelper::index(CharacteristicElement::find()->groupId($groupId)->all(), 'handle');
        // Get the old links
        if ($element->id) {
            $oldLinksById = CharacteristicLink::find()
                ->fieldId($this->id)
                ->ownerId($element->id)
                ->siteId($element->siteId)
                ->with(['characteristic'])
                ->indexBy('id')
                ->all();
        } else {
            $oldLinksById = [];
        }

        $characteristicsByHandle = CharacteristicElement::find()
            ->groupId($groupId)
            ->indexBy('handle')
            ->all();

        $oldLinksGroupedByCharacteristicHandle = ArrayHelper::index($oldLinksById, null, function ($link) {
            return $link->characteristic->handle;
        });

        $links = [];
        $prevLink = null;

        $fieldNamespace = $element->getFieldParamNamespace();

        foreach ($value as $characteristicHandle => $characteristicData) {
            // Make sure it's a valid characteristic
            if (!isset($characteristicsByHandle[$characteristicHandle])) {
                continue;
            }
            $characteristic = $characteristicsByHandle[$characteristicHandle];
            if (!isset($characteristicData['values'])) {
                continue;
            }
            foreach ($characteristicData['values'] as $valueString) {
                $valueElement = Characteristic::$plugin->characteristicValues->getValueElement($characteristic, $valueString, true);
                if (!$valueElement) {
                    continue;
                }
                $block = new CharacteristicLink();
                $block->fieldId = $this->id;
                $block->characteristicId = $characteristic->id;
                $block->valueId = $valueElement->id;
                $block->ownerId = $element->id;
                $block->siteId = $element->siteId;
                $block->setCharacteristic($characteristic);
                $block->setValue($valueElement);
                $block->setOwner($element);

                // Set the prev/next blocks
                if ($prevLink) {
                    /** @var ElementInterface $prevLink */
                    $prevLink->setNext($block);
                    /** @var ElementInterface $block */
                    $block->setPrev($prevLink);
                }
                $prevLink = $block;

                $links[] = $block;
            }
        }
        return $links;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        return parent::serializeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'characteristic/_components/fields/Characteristics_settings',
            [
                'field' => $this,
            ]
        );
    }

    /**
     * Normalizes the available sources into select input options.
     *
     * @return array
     */
    public function getSourceOptions(): array
    {
        $options = [];
        $optionNames = [];

        foreach ($this->availableSources() as $source) {
            // Make sure it's not a heading
            if (!isset($source['heading'])) {
                $options[] = [
                    'label' => Html::encode($source['label']),
                    'value' => $source['key']
                ];
                $optionNames[] = $source['label'];
            }
        }

        // Sort alphabetically
        array_multisort($optionNames, SORT_NATURAL | SORT_FLAG_CASE, $options);

        return $options;
    }

    /**
     * Returns the sources that should be available to choose from within the field's settings
     *
     * @return array
     */
    protected function availableSources(): array
    {
        return Craft::$app->getElementIndexes()->getSources(CharacteristicElement::class, 'modal');
    }

    /**
     * @param $value
     * @param ElementInterface|null $element
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \yii\base\Exception
     * @throws InvalidConfigException
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var Element $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        }
        if ($value instanceof CharacteristicLinkQuery) {
            $value = $value->getCachedResult() ?? $value->limit(null)->all();
        }

        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(CharacteristicsFieldAsset::class);

        /** @var ElementQuery|array $value */
        $variables = $this->inputTemplateVariables($value, $element);

        return Craft::$app->getView()->renderTemplate('characteristic/_components/fields/Characteristics_input', $variables);
    }

    /**
     * Returns an array of variables that should be passed to the input template.
     *
     * @param ElementQueryInterface|array|null $value
     * @param ElementInterface|null $element
     * @return array
     */
    protected function inputTemplateVariables($value = null, ElementInterface $element = null): array
    {

        $values = [];

        /** @var CharacteristicLink $characteristicItem */
        foreach ($value as $characteristicItem) {
            $characteristic = $characteristicItem->characteristic;
            $characteristicValue = $characteristicItem->value;

            if (!isset($values[$characteristic->handle])) {
                $values[$characteristic->handle] = [];
            }
            $values[$characteristic->handle][] = $characteristicValue->value;
        }
        return [
            'id' => Craft::$app->getView()->formatInputId($this->handle),
            'fieldId' => $this->id,
            'storageKey' => 'field.' . $this->id,
            'name' => $this->handle,
            'source' => $this->source,
            'value' => $values,
            'sourceElementId' => !empty($element->id) ? $element->id : null,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = 'validateCharacteristicData';

        return $rules;
    }

    /**
     * Validates the data for the characteristics
     *
     * @param ElementInterface $element
     */
    public function validateCharacteristicData(ElementInterface $element)
    {
        /** @var Element $element */
        $value = $element->getFieldValue($this->handle);

        /** @var Element $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        }
        if ($value instanceof CharacteristicLinkQuery) {
            $value = $value->getCachedResult() ?? $value->limit(null)->all();
        }

        $groupedByIds = ArrayHelper::index($value, null, function ($c) {
            return $c->characteristic->id;
        });


        $source = ElementHelper::findSource(CharacteristicElement::class, $this->source, 'index');
        $groupId = $source['criteria']['groupId'];

        $required = CharacteristicElement::find()->groupId($groupId)->required(true)->indexBy('id')->with(['values'])->all();
        if ($required) {
            foreach ($required as $characteristicId => $characteristic) {
                if (!isset($groupedByIds[$characteristicId]) || !count($groupedByIds[$characteristicId])) {
                    $element->addError($this->handle, $characteristic->title . " is required");
                }
            }
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws Throwable
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        if (!$element instanceof Element || $element->propagating) {
            return parent::afterElementSave($element, $isNew);
        }

        /** @var Element $element */
        $value = $element->getFieldValue($this->handle);

        /** @var Element $element */
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
        }
        if ($value instanceof CharacteristicLinkQuery) {
            $value = $value->getCachedResult() ?? $value->limit(null)->all();
        }

        try {
            Characteristic::$plugin->characteristicLinks->resaveLinks($value, $element, $this);
        } catch (Exception $e) {
            Craft::dd($e);
        }
        parent::afterElementSave($element, $isNew);
    }

//
//    /**
//     * @inheritDoc
//     */
//    public function modifyElementsQuery(ElementQueryInterface $query, $value)
//    {
//        if (!Characteristic::getInstance()) {
//            return null;
//        }
//
//        Characteristic::$plugin->characteristics->modifyElementsQuery($query, $value, $this);
//        return null;
//    }
}
