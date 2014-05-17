<?php

namespace Propel\Tests\Generator\Behavior\ConcreteInheritance;

/**
    * @brief This class' purpose is to find out whether there's a save which occurrs
    * without setting $this->alreadyInSave = true,
    * which causes loop-saving to occure in the inherited classes as before the fix.
    *
    * Let's take a look at the following flow:
    * 
    * Article->save() {
    *   //Without setting "$this->alreadyInSave = true"
    * 
    *   content->save()  {
    *     doSave() {
    *       if(!$this->alreadyInSave) {
    *         $this->alreadyInSave = true;
    *         
    *         category->save() {
    *           doSave() {
    *             if(!$this->alreadyInSave) {
    *               //INSERT INTO category...
    *               
    *               content->save(); //Does nothing! because content->alreadyInSave = true.
    *               
    *               article->save()  {
    *                 content::save(); //Does nothing & content isn't saved yet
    *                 
    *                 $this->setPrimaryKey(content->getPrimaryKey()); 
    *                 //sets NULL, because content isn't saved
    *                 
    *                 doSave(); 
    *                 //Error! Content's foreign key constraint fails because it is NULL.
    * 
    * @how_to_use Simply, call ConcreteArticleLoopSaveChecker->save(),
    * and then take the result from getDoesLoopParentSaveHappen().
 */
class ConcreteArticleLoopSaveCheckerMock extends \Propel\Tests\Bookstore\Behavior\ConcreteArticle
{    
	public function getDoesLoopSavingOccur()
	{
		return $this->doesLoopSavingOccur;
	}

	public function save(\Propel\Runtime\Connection\ConnectionInterface $con = null)
	{
		$this->amountOfSavers++;

		try {
			parent::save($con);
		} catch(\Exception $e) {
			//Usually, a huge exception will be thrown from save()
			//if loop-saving occurs (problems with the parent primary key set to null),
			//so we catch it in order to not spam test results.
		}

		$this->amountOfSavers--;
	}

	protected function doSave(\Propel\Runtime\Connection\ConnectionInterface $con)
	{
		if(!$this->alreadyInSave) {
			$this->alreadyInSave = true;

			if($this->amountOfSavers > 1) {
				$this->doesLoopSavingOccur = true;
			}

			$this->alreadyInSave = false;
		}

		return 1;
	}

	protected $amountOfSavers = 0;
	protected $doesLoopSavingOccur = false;
}
