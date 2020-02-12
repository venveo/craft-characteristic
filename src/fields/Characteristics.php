<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2020 Venveo
 */

namespace venveo\characteristic\fields;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\ElementNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\services\Elements;
use craft\validators\ArrayValidator;
use Exception;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use venveo\characteristic\assetbundles\characteristicsfield\CharacteristicsFieldAsset;
use venveo\characteristic\Characteristic;
use venveo\characteristic\elements\Characteristic as CharacteristicElement;
use venveo\characteristic\elements\CharacteristicLinkBlock;
use venveo\characteristic\elements\db\CharacteristicLinkBlockQuery;
use yii\base\InvalidConfigException;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 *
 */
class Characteristics extends Field implements EagerLoadingFieldInterface
{

    const PROPAGATION_METHOD_NONE = 'none';
    const PROPAGATION_METHOD_SITE_GROUP = 'siteGroup';
    const PROPAGATION_METHOD_LANGUAGE = 'language';
    const PROPAGATION_METHOD_ALL = 'all';
    /**
     * @var string|null The source key that this field can relate elements from (used if [[allowMultipleSources]] is set to false)
     */
    public $source;
    /**
     * @var string Propagation method
     */
    public $propagationMethod = self::PROPAGATION_METHOD_ALL;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('characteristic', 'Characteristics');
    }

    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [
            self::TRANSLATION_METHOD_SITE,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return CharacteristicLinkBlockQuery::class;
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
        if ($value instanceof CharacteristicLinkBlockQuery) {
            return $value;
        }
        $query = CharacteristicLinkBlock::find();
        $this->_populateQuery($query, $element);

        // Set the initially matched elements if $value is already set, which is the case if there was a validation
        // error or we're loading an entry revision.
        if ($value === '') {
            $query->setCachedResult([]);
        } else if ($element && is_array($value)) {
            $query->setCachedResult($this->_createLinkBlocksFromSerializedData($value, $element));
        }

        return $query;
    }

    /**
     * Populates the field’s [[CharacteristicLinkBlockQuery]] value based on the owner element.
     *
     * @param CharacteristicLinkBlockQuery $query
     * @param ElementInterface|null $element
     * @since 3.4.0
     */
    private function _populateQuery(CharacteristicLinkBlockQuery $query, ElementInterface $element = null)
    {
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
    private function _createLinkBlocksFromSerializedData(array $value, ElementInterface $element): array
    {
        $source = ElementHelper::findSource(CharacteristicElement::class, $this->source, 'index');
        $groupId = $source['criteria']['groupId'];
        $characteristicsById = ArrayHelper::index(CharacteristicElement::find()->groupId($groupId)->all(), 'id');

        // Get the old blocks
        if ($element->id) {
            $oldBlocksById = CharacteristicLinkBlock::find()
                ->fieldId($this->id)
                ->ownerId($element->id)
                ->siteId($element->siteId)
                ->indexBy('id')
                ->all();
        } else {
            $oldBlocksById = [];
        }

        $blocks = [];
        $prevBlock = null;

        $newBlockData = $value;

        foreach($value as $blockId => $blockData) {
            // If this is a preexisting block but we don't have a record of it,
            // check to see if it was recently duplicated.
            if (
                strpos($blockId, 'new') !== 0 &&
                !isset($oldBlocksById[$blockId]) &&
                isset(Elements::$duplicatedElementIds[$blockId]) &&
                isset($oldBlocksById[Elements::$duplicatedElementIds[$blockId]])
            ) {
                $blockId = Elements::$duplicatedElementIds[$blockId];
            }

            // Existing block?
            if (isset($oldBlocksById[$blockId])) {
                // TODO: Check values for changes
                /** @var CharacteristicLinkBlock $block */
                $block = $oldBlocksById[$blockId];
                $block->dirty = true;
            } else {
                $block = new CharacteristicLinkBlock();
                $block->fieldId = $this->id;
                $block->characteristicId = $blockData['characteristic'];
                $block->ownerId = $element->id;
                $block->siteId = $element->siteId;
            }

            $block->setOwner($element);

            if (isset($blockData['values'])) {
                $block->setValues($blockData['values']);
            }

            // Set the prev/next blocks
            if ($prevBlock) {
                /** @var ElementInterface $prevBlock */
                $prevBlock->setNext($block);
                /** @var ElementInterface $block */
                $block->setPrev($prevBlock);
            }
            $prevBlock = $block;

            $blocks[] = $block;
        }
        return $blocks;

//
        /** @var Characteristic[] $characteristics */
//        $characteristics = ArrayHelper::index(CharacteristicElement::find()->groupId($groupId)->all(), 'handle');
//        // Get the old links
//        if ($element->id) {
//            $oldLinksById = CharacteristicLinkBlock::find()
//                ->fieldId($this->id)
//                ->ownerId($element->id)
//                ->siteId($element->siteId)
////                ->with(['characteristic'])
//                ->indexBy('id')
//                ->all();
//        } else {
//            $oldLinksById = [];
//        }
//
//        $links = [];
//        $prevLink = null;
//
//        foreach ($value as $characteristicHandle => $characteristicData) {
//            // Make sure it's a valid characteristic
//            if (!isset($characteristicsByHandle[$characteristicHandle])) {
//                continue;
//            }
//            $characteristic = $characteristicsByHandle[$characteristicHandle];
//            if (!isset($characteristicData['values'])) {
//                continue;
//            }
//            foreach ($characteristicData['values'] as $valueString) {
//                $valueElement = Characteristic::$plugin->characteristicValues->getValueElement($characteristic, $valueString, true);
//                if (!$valueElement) {
//                    continue;
//                }
//                $block = new CharacteristicLink();
//                $block->fieldId = $this->id;
//                $block->characteristicId = $characteristic->id;
//                $block->valueId = $valueElement->id;
//                $block->ownerId = $element->id;
//                $block->siteId = Craft::$app->sites->getPrimarySite()->id;
//                $block->setCharacteristic($characteristic);
//                $block->setValue($valueElement);
//                $block->setOwner($element);
//
//                // Set the prev/next blocks
//                if ($prevLink) {
//                    /** @var ElementInterface $prevLink */
//                    $prevLink->setNext($block);
//                    /** @var ElementInterface $block */
//                    $block->setPrev($prevLink);
//                }
//                $prevLink = $block;
//
//                $links[] = $block;
//            }
//        }
//        return $links;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        /** @var CharacteristicLinkBlockQuery $value */
        $serialized = [];
        $new = 0;

        foreach ($value->all() as $block) {
            $blockId = $block->id ?? 'new' . ++$new;
            $serialized[$blockId] = [
            ];
        }

        return $serialized;
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
        if ($value instanceof CharacteristicLinkBlockQuery) {
            $value = $value->getCachedResult() ?? $value->limit(null)->anyStatus()->all();
        }


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
        $id = Craft::$app->getView()->formatInputId($this->handle);

        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(CharacteristicsFieldAsset::class);

//        $values = [];

//        /** @var CharacteristicLinkBlock $characteristicItem */
//        foreach ($value as $characteristicItem) {
//            $characteristic = $characteristicItem->characteristic;
//            $characteristicValue = $characteristicItem->value;
//
//            if (!isset($values[$characteristic->handle])) {
//                $values[$characteristic->handle] = [];
//            }
//            $values[$characteristic->handle][] = $characteristicValue->value;
//        }
        Craft::$app->getView()->registerAssetBundle(CharacteristicsFieldAsset::class);

//        // Safe to create the default blocks?
//        if ($createDefaultBlocks) {
//            $blockTypeJs = Json::encode($blockTypes[0]->handle);
//            for ($i = count($value); $i < $this->minBlocks; $i++) {
//                $js .= "\nmatrixInput.addBlock({$blockTypeJs});";
//            }
//        }

        $source = ElementHelper::findSource(CharacteristicElement::class, $this->source, 'index');
        $groupId = $source['criteria']['groupId'];

        $characteristics = CharacteristicElement::find()->groupId($groupId)->with(['values'])->all();

        return [
            'id' => $id,
            'name' => $this->handle,
            'characteristics' => $characteristics,
            'static' => false,
            'blocks' => $value,
            'staticBlocks' => []
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            'validateCharacteristicData',
            [
                ArrayValidator::class,
                'skipOnEmpty' => false,
                'on' => Element::SCENARIO_LIVE,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty($value, ElementInterface $element): bool
    {
        /** @var CharacteristicLinkBlockQuery $value */
        return $value->count() === 0;
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
        if ($value instanceof CharacteristicLinkBlockQuery) {
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
     * @inheritdoc
     */
    public function getIsTranslatable(ElementInterface $element = null): bool
    {
        return $this->propagationMethod !== self::PROPAGATION_METHOD_ALL;
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws Throwable
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew)
    {
        $characteristicLinkBlocksService = Characteristic::getInstance()->characteristicLinkBlocks;

        /** @var Element $element */
        if ($element->duplicateOf !== null) {
            $characteristicLinkBlocksService->duplicateBlocks($this, $element->duplicateOf, $element, true);
        } else if ($element->isFieldDirty($this->handle)) {
            $characteristicLinkBlocksService->saveField($this, $element);
        }

//         Repopulate the Matrix block query if this is a new element
        if ($element->duplicateOf || $isNew) {
            /** @var CharacteristicLinkBlockQuery $query */
            $query = $element->getFieldValue($this->handle);
            $this->_populateQuery($query, $element);
            $query->clearCachedResult();
        }

        parent::afterElementPropagate($element, $isNew);
    }


    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        if (!parent::beforeSave($isNew)) {
            return false;
        }
        return true;
    }


    /**
     * @inheritdoc
     */
    public function beforeElementDelete(ElementInterface $element): bool
    {
        if (!parent::beforeElementDelete($element)) {
            return false;
        }

        /** @var Element $element */
        // Delete any Matrix blocks that belong to this element(s)
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            $characteristicLinkBlockQuery = CharacteristicLinkBlock::find();
            $characteristicLinkBlockQuery->anyStatus();
            $characteristicLinkBlockQuery->siteId($siteId);
            $characteristicLinkBlockQuery->ownerId($element->id);

            /** @var CharacteristicLinkBlock[] $blocks */
            $blocks = $characteristicLinkBlockQuery->all();
            $elementsService = Craft::$app->getElements();

            foreach ($blocks as $block) {
                $block->deletedWithOwner = true;
                $elementsService->deleteElement($block, $element->hardDelete);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementRestore(ElementInterface $element)
    {
        /** @var Element $element */
        // Also restore any Matrix blocks for this element
        $elementsService = Craft::$app->getElements();
        foreach (ElementHelper::supportedSitesForElement($element) as $siteInfo) {
            $blocks = CharacteristicLinkBlock::find()
                ->anyStatus()
                ->siteId($siteInfo['siteId'])
                ->ownerId($element->id)
                ->trashed()
                ->andWhere(['characteristic_linkblocks.deletedWithOwner' => true])
                ->all();

            foreach ($blocks as $block) {
                $elementsService->restoreElement($block);
            }
        }

        parent::afterElementRestore($element);
    }


    /**
     * @inheritdoc
     */
    public function getEagerLoadingMap(array $sourceElements)
    {
        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select(['ownerId as source', 'id as target'])
            ->from(['{{%characteristic_linkblocks}}'])
            ->where([
                'fieldId' => $this->id,
                'ownerId' => $sourceElementIds,
            ])
            ->all();

        return [
            'elementType' => CharacteristicLinkBlock::class,
            'map' => $map,
            'criteria' => [
                'fieldId' => $this->id,
                'allowOwnerDrafts' => true,
                'allowOwnerRevisions' => true,
            ]
        ];
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

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [
            ['propagationMethod'], 'in', 'range' => [
                self::PROPAGATION_METHOD_NONE,
                self::PROPAGATION_METHOD_SITE_GROUP,
                self::PROPAGATION_METHOD_LANGUAGE,
                self::PROPAGATION_METHOD_ALL
            ]
        ];
        $rules[] = [['source'], 'required'];
        return $rules;
    }
}
