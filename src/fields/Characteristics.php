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
use craft\helpers\Html;
use craft\helpers\Json;
use Exception;
use Throwable;
use venveo\characteristic\assetbundles\characteristicsfield\CharacteristicsFieldAsset;
use venveo\characteristic\Characteristic;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicValue;
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


    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (is_array($value)) {
            $attributes = $value;
        } elseif (is_string($value)) {
            try {
                $attributes = Json::decode($value);
            } catch (Throwable $error) {
                Craft::error($error->getMessage());
                $attributes = null;
            }
            if (!is_array($attributes)) {
                $attributes = [];
            }
        } else {
            // Ensure we get links that don't have deleted elements
            $recordQuery = CharacteristicLinkRecord::find()
                ->addSelect(['link.id', 'link.characteristicId', 'link.valueId'])
                ->alias('link')
                ->leftJoin('{{%elements}} elements1', '[[elements1.id]] = [[link.characteristicId]]')
                ->leftJoin('{{%elements}} elements2', '[[elements2.id]] = [[link.valueId]]');
            $recordQuery->where(['link.fieldId' => $this->id, 'link.elementId' => $element->id]);
            $recordQuery->andWhere(['elements1.dateDeleted' => null, 'elements2.dateDeleted' => null]);
            $records = $recordQuery->asArray()->all();
            $inputData = $this->prepareDataForInput($records);
            return $inputData;
        }
        return $attributes;
//        return $this->createModel($attributes, $element);
    }

    protected function prepareDataForInput($results)
    {
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
        $valueQuery->indexBy('id');
        $values = $valueQuery->all();

        $inputData = [];

        /** @var CharacteristicLinkRecord $result */
        foreach ($results as $result) {
            $inputData[] = [
                'id' => $result['id'],
                'characteristic' => $characteristics[$result['characteristicId']],
                'value' => $values[$result['valueId']]
            ];
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
        $formattedValues = [];
        foreach($value as $characteristicItem) {
            $characteristicItem['characteristic'] = [
                'id' => $characteristicItem['characteristic']->id,
                'handle' => $characteristicItem['characteristic']->handle,
                'title' => $characteristicItem['characteristic']->title,
                'values' => array_map(function(CharacteristicValue $cvalue) {
                    return [
                        'id' => $cvalue->id,
                        'value' => $cvalue->value
                    ];
                }, $characteristicItem['characteristic']->values)
            ];
            $formattedValues[] = $characteristicItem;
        }
        return [
            'id' => Craft::$app->getView()->formatInputId($this->handle),
            'fieldId' => $this->id,
            'storageKey' => 'field.' . $this->id,
            'name' => $this->handle,
            'source' => $this->source,
            'value' => $formattedValues,
            'sourceElementId' => !empty($element->id) ? $element->id : null,
        ];
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
                if (isset($attribute['characteristic']) && $attribute['characteristic'] instanceof CharacteristicElement) {
                    $characteristic = $attribute['characteristic'];
                } else {
                    $characteristic = Characteristic::$plugin->characteristics->getCharacteristicByHandle($this->groupId, $attribute['attribute']);
                }
                if (isset($attribute['value']) && $attribute['value'] instanceof CharacteristicValue) {
                    $value = $attribute['value'];
                } else {
                    $value = Characteristic::$plugin->characteristicValues->getOrCreateValueElement($characteristic, $attribute['value']);
                }
                if ($characteristic) {
                    $linksToResave[] = [
                        'characteristic' => $characteristic,
                        'value' => $value
                    ];
                }
            }

            Characteristic::$plugin->characteristicLinks->resaveLinks($linksToResave, $element, $this);
        } catch (Exception $e) {
            Craft::dd($e);
        }
        parent::afterElementSave($element, $isNew);
    }
}
