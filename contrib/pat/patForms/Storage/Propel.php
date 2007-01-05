<?php

class patForms_Storage_Propel extends patForms_Storage
{
	private $peer;
	private $peername;

	public function setStorageLocation($peername) {

		$this->peer = new $peername();
		$this->peername = $peername;

		$parts = explode('.', explode('.', $this->peer->getOMClass()));
		$this->classname = array_pop($parts);

		$this->setPrimaryField('Id');
	}

	private function getCriteria($values) {

		$object = new $this->classname();
		//$object->populateFromArray($values); //TODO use a workaround until we'll get phpNamed keys in populateFromArray()
		$object = $this->populateObjectFromArray($object, $values);
		return $object->buildPkeyCriteria();
	}

   /**
	* get an entry
	*
	* This tries to find an entry in the storage container
	* that matches the current data that has been set in the
	* form and populates the form with the data of this
	* entry
	*
	* @access     public
	* @param      object patForms		patForms object that should be stored
	* @return     boolean				true on success
	*/
	public function loadEntry(&$form) {

		if (!$object = $this->_entryExists($form->getValues())) {
			// entry does not exists (why return an array here??)
			return array();
		}

		$form->setValues($object->toArray());
		return true;
	}

	public function validateEntry(&$form) {

		if (!$object = $this->_entryExists($form->getValues())) {
			$object = new $this->classname();
		}
		//$object->populateFromArray($form->getValues()); //TODO use a workaround until we'll get phpNamed keys in populateFromArray()
		$object = $this->populateObjectFromArray($object, $form->getValues());
		$result = $object->validate();

		if ($result !== true) {
			$mapBuilder = $this->peer->getMapBuilder();
			$dbMap = $mapBuilder->getDatabaseMap();
			foreach ($result as $colname => $error) {
				list($tablename, $colname) = explode('.', $colname);
				$column = $dbMap->getTable($tablename)->getColumn($colname);
				$element = $form->getElement($column->getPhpName());
				$element->addValidatorErrorCodes(array(
					'C' => array(
						1 => $error->getMessage() . ' (occured in Storage)',
					),
				), 1000);
				$element->addValidationError(1001);
			}
			return false;
		}
	}

   /**
	* adds an entry to the storage
	*
	* @param      object patForms		patForms object that should be stored
	* @return     boolean				true on success
	*/
	public function _addEntry(&$form) {

		$object = new $this->classname();
		//$object->populateFromArray($form->getValues()); //TODO use a workaround until we'll get phpNamed keys in populateFromArray()
		$object = $this->populateObjectFromArray($object, $form->getValues());
		$object->save();
		return true;
	}

   /**
	* updates an entry in the storage
	*
	* @param      object patForms		patForms object that should be stored
	* @return     boolean				true on success
	*/
	public function _updateEntry(&$form, $primary) {

		$object = $this->_entryExists($form->getValues());
		//$object->populateFromArray($form->getValues()); //TODO use a workaround until we'll get phpNamed keys in populateFromArray()
		$object = $this->populateObjectFromArray($object, $form->getValues());
		$object->save();
		return true;
	}

   /**
	* check, whether an entry exists
	*
	* @access     private
	* @param      array
	*/
	public function _entryExists($values) {

		// This method gets called multiple times, e.g. when an existing
		// object gets updated. We'll therefor cache results locally using
		// a criteria string representation as hash.

		static $objects;
		$criteria = $this->getCriteria($values);
		$hash = $criteria->toString();

		if (isset($objects[$hash])) {
			return $objects[$hash];
		}

		$objects[$hash] = $this->peer->doSelectOne($criteria);

		if (empty($objects[$hash])) {
			return false;
		}
		return $objects[$hash];
	}

	// this method is just a workaround

	private function populateObjectFromArray($object, $values) {

		foreach (array_keys($object->toArray()) as $key) {
			if (array_key_exists($key, $values)) {
				$object->{'set' . $key}($values[$key]);
			}
		}
		return $object;
	}
}
