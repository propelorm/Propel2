/**
 * rank column
 */
const RANK_COL = "<?php echo $tableName ?>.<?php echo $rankColumn ?>";

<?php if ($useScope) :?>

    <?php if ($multiScope) :?>
/**
* If defined, the `SCOPE_COL` contains a json_encoded array with all columns.
* @var bool
*/
const MULTI_SCOPE_COL = true;

    <?php endif?>

/**
* Scope column for the set
*/
const SCOPE_COL = <?php echo $scope ?>;

<?php endif ?>
