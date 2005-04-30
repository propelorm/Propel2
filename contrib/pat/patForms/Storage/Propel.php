<?php

class patForms_Storage_Propel extends patForms_Storage
{
	private $peer;
	private $peername;

	function setStorageLocation($peername)
	{

		$this->peer = new $peername();
		$this->peername = $peername;
		$this->classname = array_pop(explode('.', $this->peer->getOMClass()));

		$this->setPrimaryField('Id');
	}

	private function getCriteria($values) {

		$object = new $this->classname();
		//TODO use a workaround until we'll get phpNamed keys in populateFromArray()
		//$object->populateFromArray($values);
		$object = $this->populateObjectFromArray($object, $values);
		return $object->buildPkeyCriteria();
	}

	private function populateObjectFromArray($object, $values) {

		foreach(array_keys($object->toArray()) as $key) {
			if(array_key_exists($key, $values)) {
				$object->{'set' . $key}($values[$key]);
			}
		}
		return $object;
	}

   /**
	* get an entry
	*
	* This tries to find an entry in the storage container
	* that matches the current data that has been set in the
	* form and populates the form with the data of this
	* entry
	*
	* @access	public
	* @param	object patForms		patForms object that should be stored
	* @return	boolean				true on success
	*/
	function loadEntry(&$form)
	{
		if(!$object = $this->_entryExists($form->getValues())) {
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
		$object->populateFromArray($form->getValues());
		$result = $object->validate();

		if ($result !== true) {
			$mapBuilder = $this->peer->getMapBuilder();
			$dbMap = $mapBuilder->getDatabaseMap();
			foreach($result as $colname => $error) {
				list($tablename, $colname) = explode('.', $colname);
				$column = $dbMap->getTable($tablename)->getColumn($colname);
				$element = $form->getElement($column->getPhpName());
				$element->addValidatorErrorCodes(array(
					'de' => array(
						1 => 'de: ' . $error->getMessage(),
					),
					'C' => array(
						1 => 'C: ' . $error->getMessage(),
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
	* The entry will be appended at the end of the file.
	*
	* @abstract
	* @param	object patForms		patForms object that should be stored
	* @return	boolean				true on success
	*/
	function _addEntry(&$form)
	{
		$object = new $this->classname();
		$object->populateFromArray($form->getValues());
		$object->save();
		return true;
	}

   /**
	* updates an entry in the storage
	*
	* Implement this in the concrete storage container.
	*
	* @abstract
	* @param	object patForms		patForms object that should be stored
	* @return	boolean				true on success
	*/
	function _updateEntry(&$form, $primary)
	{
		$object = $this->_entryExists($form->getValues());
		$object->populateFromArray($form->getValues());
		$object->save();
		return true;
	}

   /**
	* check, whether an entry exists
	*
	* @access	private
	* @param	array
	*/
	function _entryExists($values)
	{
		$criteria = $this->getCriteria($values);
		$object = $this->peer->doSelectOne($criteria);

		if(empty($object))
			return false;

		return $object;
	}
}
?>