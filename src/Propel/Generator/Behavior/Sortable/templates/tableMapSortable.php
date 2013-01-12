/**
 * rank column
 */
const RANK_COL = "<?php echo $tableName ?>.<?php echo $rankColumn ?>";

<?php if ($useScope) :?>
/**
 * Scope column for the set
 */
const SCOPE_COL = "<?php echo $tableName ?>.<?php echo $scopeColumn ?>";

<?php endif ?>