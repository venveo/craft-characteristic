<?php
/**
 * Characteristic plugin for Craft CMS 3.x
 *
 * Drill-drown on element characteristics
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\characteristic\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\errors\ElementNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use Throwable;
use venveo\characteristic\elements\CharacteristicLink;
use venveo\characteristic\elements\CharacteristicLinkBlock;
use venveo\characteristic\elements\db\CharacteristicLinkBlockQuery;
use venveo\characteristic\fields\Characteristics as CharacteristicsField;
use yii\db\Exception;

/**
 * @author    Venveo
 * @package   Characteristic
 * @since     1.0.0
 */
class CharacteristicLinkBlocks extends Component
{

    public function saveField(CharacteristicsField $field, ElementInterface $owner)
    {
        /** @var Element $owner */
        $elementsService = Craft::$app->getElements();
        /** @var CharacteristicLinkBlockQuery $query */
        $query = $owner->getFieldValue($field->handle);
        /** @var CharacteristicLinkBlock[] $blocks */
        if (($blocks = $query->getCachedResult()) !== null) {
            $saveAll = false;
        } else {
            $blocksQuery = clone $query;
            $blocks = $blocksQuery->anyStatus()->all();
            $saveAll = true;
        }

        $blockIds = [];

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            foreach ($blocks as $block) {
                if ($saveAll || !$block->id || $block->dirty) {
                    $block->ownerId = $owner->id;
                    $elementsService->saveElement($block, false);
                }

                $blockIds[] = $block->id;
            }

            // Delete any blocks that shouldn't be there anymore
            $this->_deleteOtherBlocks($field, $owner, $blockIds);

            // Should we duplicate the blocks to other sites?
            if (
                $field->propagationMethod !== CharacteristicsField::PROPAGATION_METHOD_ALL &&
                ($owner->propagateAll || !empty($owner->newSiteIds))
            ) {
                // Find the owner's site IDs that *aren't* supported by this site's Matrix blocks
                $ownerSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($owner), 'siteId');
                $fieldSiteIds = $this->getSupportedSiteIds($field->propagationMethod, $owner);
                $otherSiteIds = array_diff($ownerSiteIds, $fieldSiteIds);

                // If propagateAll isn't set, only deal with sites that the element was just propagated to for the first time
                if (!$owner->propagateAll) {
                    $otherSiteIds = array_intersect($otherSiteIds, $owner->newSiteIds);
                }

                if (!empty($otherSiteIds)) {
                    // Get the original element and duplicated element for each of those sites
                    /** @var Element[] $otherTargets */
                    $otherTargets = $owner::find()
                        ->drafts($owner->getIsDraft())
                        ->revisions($owner->getIsRevision())
                        ->id($owner->id)
                        ->siteId($otherSiteIds)
                        ->anyStatus()
                        ->all();

                    // Duplicate Matrix blocks, ensuring we don't process the same blocks more than once
                    $handledSiteIds = [];

                    $cachedQuery = clone $query;
                    $cachedQuery->anyStatus();
                    $cachedQuery->setCachedResult($blocks);
                    $owner->setFieldValue($field->handle, $cachedQuery);

                    foreach ($otherTargets as $otherTarget) {
                        // Make sure we haven't already duplicated blocks for this site, via propagation from another site
                        if (isset($handledSiteIds[$otherTarget->siteId])) {
                            continue;
                        }

                        $this->duplicateBlocks($field, $owner, $otherTarget);

                        // Make sure we don't duplicate blocks for any of the sites that were just propagated to
                        $sourceSupportedSiteIds = $this->getSupportedSiteIds($field->propagationMethod, $otherTarget);
                        $handledSiteIds = array_merge($handledSiteIds, array_flip($sourceSupportedSiteIds));
                    }

                    $owner->setFieldValue($field->handle, $query);
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes blocks from an owner element
     *
     * @param MatrixField $field The Matrix field
     * @param ElementInterface The owner element
     * @param int[] $except Block IDs that should be left alone
     * @throws Throwable
     */
    private function _deleteOtherBlocks(CharacteristicsField $field, ElementInterface $owner, array $except)
    {
        /** @var Element $owner */
        $deleteBlocks = CharacteristicLinkBlock::find()
            ->ownerId($owner->id)
            ->fieldId($field->id)
            ->siteId($owner->siteId)
            ->andWhere(['not', ['elements.id' => $except]])
            ->all();

        $elementsService = Craft::$app->getElements();

        foreach ($deleteBlocks as $deleteBlock) {
            $elementsService->deleteElement($deleteBlock);
        }
    }

    /**
     * @param CharacteristicsField $field
     * @param ElementInterface $source
     * @param ElementInterface $target
     * @param bool $checkOtherSites
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     * @throws \yii\base\Exception
     */
    public function duplicateBlocks(CharacteristicsField $field, ElementInterface $source, ElementInterface $target, bool $checkOtherSites = false)
    {
        /** @var Element $source */
        /** @var Element $target */
        $elementsService = Craft::$app->getElements();
        /** @var CharacteristicLinkBlockQuery $query */
        $query = $source->getFieldValue($field->handle);
        /** @var CharacteristicLinkBlock[] $blocks */
        if (($blocks = $query->getCachedResult()) === null) {
            $blocksQuery = clone $query;
            $blocks = $blocksQuery->all();
        }
        $newBlockIds = [];

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            foreach ($blocks as $block) {
                /** @var CharacteristicLinkBlock $newBlock */
                $newBlock = $elementsService->duplicateElement($block, [
                    'ownerId' => $target->id,
                    'owner' => $target,
                    'siteId' => $target->siteId,
                    'propagating' => false,
                ]);
                $newBlockIds[] = $newBlock->id;
            }

            // Delete any blocks that shouldn't be there anymore
            $this->_deleteOtherBlocks($field, $target, $newBlockIds);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Duplicate blocks for other sites as well?
        if ($checkOtherSites && $field->propagationMethod !== CharacteristicsField::PROPAGATION_METHOD_ALL) {
            // Find the target's site IDs that *aren't* supported by this site's Matrix blocks
            $targetSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($target), 'siteId');
            $fieldSiteIds = $this->getSupportedSiteIds($field->propagationMethod, $target);
            $otherSiteIds = array_diff($targetSiteIds, $fieldSiteIds);

            if (!empty($otherSiteIds)) {
                // Get the original element and duplicated element for each of those sites
                /** @var Element[] $otherSources */
                $otherSources = $target::find()
                    ->drafts($source->getIsDraft())
                    ->revisions($source->getIsRevision())
                    ->id($source->id)
                    ->siteId($otherSiteIds)
                    ->anyStatus()
                    ->all();
                /** @var Element[] $otherTargets */
                $otherTargets = $target::find()
                    ->drafts($target->getIsDraft())
                    ->revisions($target->getIsRevision())
                    ->id($target->id)
                    ->siteId($otherSiteIds)
                    ->anyStatus()
                    ->indexBy('siteId')
                    ->all();

                // Duplicate Matrix blocks, ensuring we don't process the same blocks more than once
                $handledSiteIds = [];

                foreach ($otherSources as $otherSource) {
                    // Make sure the target actually exists for this site
                    if (!isset($otherTargets[$otherSource->siteId])) {
                        continue;
                    }

                    // Make sure we haven't already duplicated blocks for this site, via propagation from another site
                    if (in_array($otherSource->siteId, $handledSiteIds, false)) {
                        continue;
                    }

                    $this->duplicateBlocks($field, $otherSource, $otherTargets[$otherSource->siteId]);

                    // Make sure we don't duplicate blocks for any of the sites that were just propagated to
                    $sourceSupportedSiteIds = $this->getSupportedSiteIds($field->propagationMethod, $otherSource);
                    $handledSiteIds = array_merge($handledSiteIds, $sourceSupportedSiteIds);
                }
            }
        }
    }

    /**
     * Returns the site IDs that are supported by Matrix blocks for the given propagation method and owner element.
     *
     * @param string $propagationMethod
     * @param ElementInterface $owner
     * @return int[]
     * @throws \yii\base\Exception
     * @since 3.3.18
     */
    public function getSupportedSiteIds(string $propagationMethod, ElementInterface $owner): array
    {
        /** @var Element $owner */
        /** @var Site[] $allSites */
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(), 'id');
        $ownerSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($owner), 'siteId');
        $siteIds = [];

        foreach ($ownerSiteIds as $siteId) {
            switch ($propagationMethod) {
                case CharacteristicsField::PROPAGATION_METHOD_NONE:
                    $include = $siteId == $owner->siteId;
                    break;
                case CharacteristicsField::PROPAGATION_METHOD_SITE_GROUP:
                    $include = $allSites[$siteId]->groupId == $allSites[$owner->siteId]->groupId;
                    break;
                case CharacteristicsField::PROPAGATION_METHOD_LANGUAGE:
                    $include = $allSites[$siteId]->language == $allSites[$owner->siteId]->language;
                    break;
                default:
                    $include = true;
                    break;
            }

            if ($include) {
                $siteIds[] = $siteId;
            }
        }

        return $siteIds;
    }
}
