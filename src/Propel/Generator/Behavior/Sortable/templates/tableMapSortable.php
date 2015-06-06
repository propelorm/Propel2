/**
 * rank field
 */
const RANK_COL = "<?php echo $tableName ?>.<?php echo $rankField ?>";

<?php if ($useScope) :?>

    <?php if ($multiScope) :?>
/**
* If defined, the `SCOPE_COL` contains a json_encoded array with all fields.
* @var boolean
*/
const MULTI_SCOPE_COL = true;

    <?php endif?>

/**
* Scope field for the set
*/
const SCOPE_COL = <?php echo $scope ?>;

<?php endif ?>
