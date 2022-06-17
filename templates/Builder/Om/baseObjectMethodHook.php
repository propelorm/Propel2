
<?php if ($preSave) :?>
    /**
     * Code to be run before persisting the object
     * @param ConnectionInterface|null $con
     * @return bool
     */
    public function preSave(?ConnectionInterface $con = null): bool
    {
        <?php if ($hasBaseClass) : ?>
        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }
        <?php endif?>
        return true;
    }

<?php endif?>
<?php if ($postSave) :?>
    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface|null $con
     * @return void
     */
    public function postSave(?ConnectionInterface $con = null): void
    {
        <?php if ($hasBaseClass) : ?>
        if (is_callable('parent::postSave')) {
            parent::postSave($con);
        }
        <?php endif?>
    }

<?php endif?>
<?php if ($preInsert) :?>
    /**
     * Code to be run before inserting to database
     * @param ConnectionInterface|null $con
     * @return bool
     */
    public function preInsert(?ConnectionInterface $con = null): bool
    {
        <?php if ($hasBaseClass) : ?>
        if (is_callable('parent::preInsert')) {
            return parent::preInsert($con);
        }
        <?php endif?>
        return true;
    }

<?php endif?>
<?php if ($postInsert) :?>
    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface|null $con
     * @return void
     */
    public function postInsert(?ConnectionInterface $con = null): void
    {
        <?php if ($hasBaseClass) : ?>
        if (is_callable('parent::postInsert')) {
            parent::postInsert($con);
        }
        <?php endif?>
    }

<?php endif?>
<?php if ($preUpdate) :?>
    /**
     * Code to be run before updating the object in database
     * @param ConnectionInterface|null $con
     * @return bool
     */
    public function preUpdate(?ConnectionInterface $con = null): bool
    {
        <?php if ($hasBaseClass) : ?>
        if (is_callable('parent::preUpdate')) {
            return parent::preUpdate($con);
        }
        <?php endif?>
        return true;
    }

<?php endif?>
<?php if ($postUpdate) :?>
    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface|null $con
     * @return void
     */
    public function postUpdate(?ConnectionInterface $con = null): void
    {
        <?php if ($hasBaseClass) : ?>
        if (is_callable('parent::postUpdate')) {
            parent::postUpdate($con);
        }
        <?php endif?>
    }

<?php endif?>
<?php if ($preDelete) :?>
    /**
     * Code to be run before deleting the object in database
     * @param ConnectionInterface|null $con
     * @return bool
     */
    public function preDelete(?ConnectionInterface $con = null): bool
    {
        <?php if ($hasBaseClass) : ?>
        if (is_callable('parent::preDelete')) {
            return parent::preDelete($con);
        }
        <?php endif?>
        return true;
    }

<?php endif?>
<?php if ($postDelete) :?>
    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface|null $con
     * @return void
     */
    public function postDelete(?ConnectionInterface $con = null): void
    {
        <?php if ($hasBaseClass) : ?>
        if (is_callable('parent::postDelete')) {
            parent::postDelete($con);
        }
        <?php endif?>
    }

<?php endif;
