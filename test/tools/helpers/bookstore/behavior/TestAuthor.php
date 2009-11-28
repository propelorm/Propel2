<?php 

class TestAuthor extends Author {
	public function preInsert(PropelPDO $con)
	{
		parent::preInsert($con);
		$this->setFirstName('PreInsertedFirstname');
		return true;
	}

	public function postInsert(PropelPDO $con)
	{
		parent::postInsert($con);
		$this->setLastName('PostInsertedLastName');
	}

	public function preUpdate(PropelPDO $con)
	{
		parent::preUpdate($con);
		$this->setFirstName('PreUpdatedFirstname');
		return true;
	}

	public function postUpdate(PropelPDO $con)
	{
		parent::postUpdate($con);
		$this->setLastName('PostUpdatedLastName');
	}

	public function preSave(PropelPDO $con)
	{
		parent::preSave($con);
		$this->setEmail("pre@save.com");
		return true;
	}

	public function postSave(PropelPDO $con)
	{
		parent::postSave($con);
		$this->setAge(115);
	}

	public function preDelete(PropelPDO $con)
	{
		parent::preDelete($con);
		$this->setFirstName("Pre-Deleted");
		return true;
	}

	public function postDelete(PropelPDO $con)
	{
		parent::postDelete($con);
		$this->setLastName("Post-Deleted");
	}
}

class TestAuthorDeleteFalse extends TestAuthor
{
	public function preDelete(PropelPDO $con)
	{
		parent::preDelete($con);
		$this->setFirstName("Pre-Deleted");
		return false;
	}
}
class TestAuthorSaveFalse extends TestAuthor
{
	public function preSave(PropelPDO $con)
	{
		parent::preSave($con);
		$this->setEmail("pre@save.com");
		return false;
	}
	
}