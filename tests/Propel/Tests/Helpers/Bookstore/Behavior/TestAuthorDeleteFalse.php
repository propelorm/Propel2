<?php

namespace Propel\Tests\Helpers\Bookstore\Behavior;

use Propel\Runtime\Connection\PropelPDO;

class TestAuthorDeleteFalse extends TestAuthor
{
	public function preDelete(PropelPDO $con = null)
	{
		parent::preDelete($con);
		$this->setFirstName("Pre-Deleted");
		return false;
	}
}
