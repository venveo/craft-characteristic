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
use venveo\characteristic\assetbundles\characteristicsfield\CharacteristicsFieldAsset;
use venveo\characteristic\Characteristic;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicValue;
use venveo\characteristic\records\CharacteristicLink as CharacteristicLinkRecord;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class Characteristics extends Field
{
    // Public Properties
    // =========================================================================


    /**
     * @var string|string[]|null The source keys that this field can relate elements from (used if [[allowMultipleSources]] is set to true)
     */
    public $sources = '*';

    /**
     * @var string|null The source key that this field can relate elements from (used if [[allowMultipleSources]] is set to false)
     */
    public $source;

    /**
     * @var int|null The maximum number of relations this field can have (used if [[allowLimit]] is set to true)
     */
    public $limit;

    /**
     * @var bool Whether to allow the Limit setting
     */
    public $allowLimit = true;

    /**
     * @var string|null The JS class that should be initialized for the input
     */
    protected $inputJsClass;

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


    /**
     * @inheritdoc
     */
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [self::TRANSLATION_METHOD_NONE];
    }

    // Public Methods
    // =========================================================================


    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Not possible to have no sources selected
        if (!$this->sources) {
            $this->sources = '*';
        }
    }


    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'sources';
        $attributes[] = 'source';
        $attributes[] = 'limit';

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
            $recordQuery->andWhere(['elements1.dateDeleted' => null]);
            $recordQuery->andWhere(['elements2.dateDeleted' => null]);
            $records = $recordQuery->asArray()->all();
            $inputData = $this->prepareDataForInput($records);
            return $inputData;
        }
        return $attributes;
//        return $this->createModel($attributes, $element);
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
        return [
            'jsClass' => $this->inputJsClass,
            'id' => Craft::$app->getView()->formatInputId($this->handle),
            'fieldId' => $this->id,
            'storageKey' => 'field.' . $this->id,
            'name' => $this->handle,
            'elements' => $value,
            'source' => $this->source,
            'value' => $value,
            'sourceElementId' => !empty($element->id) ? $element->id : null,
        ];
    }

    /**
     * Returns an array of the source keys the field should be able to select elements from.
     *
     * @param ElementInterface|null $element
     * @return array|string
     */
    protected function inputSources(ElementInterface $element = null)
    {
        return $this->source;
    }

    /**
     * Returns any additional criteria parameters limiting which elements the field should be able to select.
     *
     * @return array
     */
    protected function inputSelectionCriteria(): array
    {
        return [];
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
                if (isset($attribute['characteristic']) && $attribute['characteristic'] instanceof \venveo\characteristic\elements\Characteristic) {
                    $characteristic = $attribute['characteristic'];
                } else {
                    $characteristic = Characteristic::$plugin->characteristics->getCharacteristicByHandle($this->groupId, $attribute['attribute']);
                }
                if (isset($attribute['value']) && $attribute['value'] instanceof \venveo\characteristic\elements\CharacteristicValue) {
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
        } catch (\Exception $e) {
            Craft::dd($e);
        }
        parent::afterElementSave($element, $isNew);
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
        $values = [];
        $characteristics = [];
        $characteristicQuery = CharacteristicElement::find();
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
}
