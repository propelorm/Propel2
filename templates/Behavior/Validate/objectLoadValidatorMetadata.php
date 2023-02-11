
/**
 * Configure validators constraints. The Validator object uses this method
 * to perform object validation.
 *
 * @param ClassMetadata $metadata
 */
static public function loadValidatorMetadata(ClassMetadata $metadata)
{
<?php foreach($constraints as $constraint) : ?>
    $metadata->addPropertyConstraint('<?php echo $constraint['column']; ?>', new <?php echo $constraint['validator']; ?>(<?php  echo((isset($constraint['options'])) ? $constraint['options'] : ''); ?>));
<?php endforeach; ?>
}
