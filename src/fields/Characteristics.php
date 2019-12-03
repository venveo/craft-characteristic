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
use craft\elements\MatrixBlock;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\services\Elements;
use craft\web\assets\matrix\MatrixAsset;
use Exception;
use Throwable;
use venveo\characteristic\assetbundles\characteristicsfield\CharacteristicsFieldAsset;
use venveo\characteristic\Characteristic;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicLink;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\elements\db\CharacteristicLinkQuery;
use venveo\characteristic\records\CharacteristicLink as CharacteristicLinkRecord;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 * @property mixed $settingsHtml
 * @property array $sourceOptions
 */
class Characteristics extends Field
{
    // Public Properties
    // =========================================================================

    /**
     * @var string|null The source key that this field can relate elements from (used if [[allowMultipleSources]] is set to false)
     */
    public $source;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('characteristic', 'Characteristics');
    }

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [self::TRANSLATION_METHOD_NONE];
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

        $oldLinksGroupedByCharacteristicHandle = ArrayHelper::index($oldLinksById, null, function($link) {
            return $link->characteristic->handle;
        });

        $links = [];
        $prevLink = null;

        $fieldNamespace = $element->getFieldParamNamespace();
        $baseBlockFieldNamespace = $fieldNamespace ? "{$fieldNamespace}.{$this->handle}" : null;


        // TODO: Someday, support deltas...
// Was the value posted in the new (delta) format?
//        if (isset($value['blocks']) || isset($value['sortOrder'])) {
//            $newBlockData = $value['blocks'] ?? [];
//            $newSortOrder = $value['sortOrder'] ?? array_keys($oldLinksById);
//            if ($baseBlockFieldNamespace) {
//                $baseBlockFieldNamespace .= '.blocks';
//            }
//        } else {
//            $newBlockData = $value;
//            $newSortOrder = array_keys($value);
//        }

        foreach ($value as $characteristicHandle => $characteristicData) {
//            if (isset($newBlockData[$blockId])) {
//                $blockData = $newBlockData[$blockId];
//            } else if (
//                isset(Elements::$duplicatedElementSourceIds[$blockId]) &&
//                isset($newBlockData[Elements::$duplicatedElementSourceIds[$blockId]])
//            ) {
//                // $blockId is a duplicated block's ID, but the data was sent with the original block ID
//                $blockData = $newBlockData[Elements::$duplicatedElementSourceIds[$blockId]];
//            } else {
//                $blockData = [];
//            }

            // If this is a preexisting block but we don't have a record of it,
            // check to see if it was recently duplicated.

            // Existing block?
            if (isset($oldLinksGroupedByCharacteristicHandle[$characteristicHandle])) {
                // TODO
                die('hi');
            } else {
                // Make sure it's a valid characteristic
                if (!isset($characteristicsByHandle[$characteristicHandle])) {
                    continue;
                }
                $characteristic = $characteristicsByHandle[$characteristicHandle];
                if (!isset($characteristicData['values'])) {
                    continue;
                }
                foreach ($characteristicData['values'] as $valueString) {
                    $valueElement = Characteristic::$plugin->characteristicValues->getOrCreateValueElement($characteristic, $valueString);
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
                return $links;
            }
        }
    }

    protected function prepareDataForInputFromPost($data)
    {
        $source = ElementHelper::findSource(CharacteristicElement::class, $this->source, 'index');
        $groupId = $source['criteria']['groupId'];

        $inputData = [];
        $index = 0;
        foreach ($data as $datum) {
            $values = [];
            /** @var CharacteristicElement $characteristic */
            $characteristic = \venveo\characteristic\elements\Characteristic::find()->groupId($groupId)->handle($datum['attribute'])->with(['values'])->one();
            if (isset($datum['value']) && is_array($datum['value'])) {
                $values = array_map(function ($value) use ($characteristic) {
                    return Characteristic::$plugin->characteristicValues->getOrCreateValueElement($characteristic, $value);
                }, $datum['value']);
            }
            $cdata = [
                'index' => $index++,
                'characteristic' => $characteristic,
                'values' => $values
            ];
            $inputData[$characteristic->id] = $cdata;
        }

        return $inputData;
    }

    /**
     * @param $results
     * @return array
     */
    protected function prepareDataForInputFromDb($results)
    {
        // First we'll look up all the values and characteristic elements we need
        $valueIds = [];
        $characteristicIds = [];
        /** @var CharacteristicLinkRecord $result */
        foreach ($results as $result) {
            $valueIds[] = $result['valueId'];
            $characteristicIds[] = $result['characteristicId'];
        }

        $characteristicQuery = CharacteristicElement::find();
        $characteristicQuery->with(['values']);
        $characteristicQuery->id($characteristicIds);
        $characteristicQuery->indexBy('id');
        $characteristics = $characteristicQuery->all();

        $valueQuery = CharacteristicValue::find();
        $valueQuery->id($valueIds);
        $valueQuery->orderBy('sortOrder ASC');
        $valueQuery->indexBy('id');
        $values = $valueQuery->all();

        $inputData = [];

        // We need to construct an array of characteristics and an array of its values
        /** @var CharacteristicLinkRecord $result */
        foreach ($results as $index => $result) {
            if (!isset($inputData[$result['characteristicId']])) {
                $inputData[$result['characteristicId']] = [];
                $inputData[$result['characteristicId']]['index'] = $index;
                $inputData[$result['characteristicId']]['characteristic'] = $characteristics[$result['characteristicId']];
                $inputData[$result['characteristicId']]['values'] = [];
            }
            $inputData[$result['characteristicId']]['values'][] = $values[$result['valueId']];
        }

        return $inputData;
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
     * @inheritdoc
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
        foreach($value as $val) {
        }

        /** @var CharacteristicLink $characteristicItem */
//        foreach ($data as $group) {
//            $formatted = [
//                'characteristic' => [
//                    'id' => $characteristicItem->characteristic->id,
//                    'handle' => $characteristicItem->characteristic->handle,
//                    'title' => $characteristicItem->characteristic->title,
//                    'values' => array_map(function (CharacteristicValue $cvalue) {
//                        return [
//                            'id' => $cvalue->id,
//                            'value' => $cvalue->value
//                        ];
//                    }, $characteristicItem->characteristic->values)
//                ],
//            ];
//            $formattedValues[] = $formatted;
//        }
        return [
            'id' => Craft::$app->getView()->formatInputId($this->handle),
            'fieldId' => $this->id,
            'storageKey' => 'field.' . $this->id,
            'name' => $this->handle,
            'source' => $this->source,
            'value' => $value,
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

        $source = ElementHelper::findSource(CharacteristicElement::class, $this->source, 'index');
        $groupId = $source['criteria']['groupId'];

        $required = CharacteristicElement::find()->groupId($groupId)->required(true)->indexBy('id')->with(['values'])->all();
        if ($required) {
            foreach ($required as $characteristicId => $characteristic) {
                if (!isset($value[$characteristicId]) || !count($value[$characteristicId]['values'])) {
                    $element->addError($this->handle, $characteristic->title . " is required");
                }
            }
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        if (!$element instanceof Element || $element->propagating) {
            return parent::afterElementSave($element, $isNew);
        }
        $attributes = $element->getFieldValue($this->handle);
        if (!is_iterable($attributes)) {
            return parent::afterElementSave($element, $isNew);
        }

        try {
            $linksToResave = [];
            foreach ($attributes as $attribute) {
                $linksToResave[] = [
                    'characteristic' => $attribute['characteristic'],
                    'values' => $attribute['values']
                ];
            }

            Characteristic::$plugin->characteristicLinks->resaveLinks($linksToResave, $element, $this);
        } catch (Exception $e) {
            Craft::dd($e);
        }
        parent::afterElementSave($element, $isNew);
    }

    protected function formatForInput() {

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
