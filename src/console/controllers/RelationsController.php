<?php

namespace venveo\characteristic\console\controllers;

use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Console;
use venveo\characteristic\Characteristic;
use yii\console\ExitCode;

/**
 * Repairs owner -> value relations that Craft 4.8+'s deleteLeftoverRelations() removed.
 */
class RelationsController extends Controller
{
    /**
     * @var bool Report what would change without writing anything.
     */
    public bool $dryRun = false;

    /**
     * @var bool Also delete owner relations that no live link block backs.
     */
    public bool $prune = false;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['dryRun', 'prune']);
    }

    /**
     * Rebuilds owner relations for every canonical, non-trashed element that owns link blocks.
     *
     *     php craft characteristic/relations/resync --dry-run
     *     php craft characteristic/relations/resync
     */
    public function actionResync(): int
    {
        $owners = (new Query())
            ->select(['lb.ownerId', 'lb.fieldId'])
            ->distinct()
            ->from(['lb' => '{{%characteristic_linkblocks}}'])
            ->innerJoin(['o' => Table::ELEMENTS], '[[o.id]] = [[lb.ownerId]]')
            ->where(['o.draftId' => null, 'o.revisionId' => null, 'o.dateDeleted' => null])
            ->orderBy(['lb.ownerId' => SORT_ASC])
            ->all();

        $service = Characteristic::$plugin->characteristicLinkBlocks;
        $touched = 0;
        $missingTotal = 0;
        $extraTotal = 0;

        foreach ($owners as $row) {
            $result = $service->rebuildOwnerRelations((int)$row['fieldId'], (int)$row['ownerId'], $this->dryRun, $this->prune);
            if ($result['missing'] || $result['extra']) {
                $touched++;
                $missingTotal += $result['missing'];
                $extraTotal += $result['extra'];
                $this->stdout(sprintf("owner %d field %d: missing %d, extra %d\n", $row['ownerId'], $row['fieldId'], $result['missing'], $result['extra']));
            }
        }

        $this->stdout(sprintf(
            "%s %d owners checked, %d need changes: %d relations %s, %d unbacked relations %s.\n",
            $this->dryRun ? '[dry run]' : 'Done.',
            count($owners),
            $touched,
            $missingTotal,
            $this->dryRun ? 'would be added' : 'added',
            $extraTotal,
            $this->prune ? ($this->dryRun ? 'would be deleted' : 'deleted') : 'left in place (use --prune)'
        ), Console::FG_GREEN);

        return ExitCode::OK;
    }
}
