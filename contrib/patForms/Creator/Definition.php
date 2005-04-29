<?php

class patForms_Datasource_Propel {

	private $peername;
	private $label;
	private $value;

	public function __construct($conf) {

		$this->peername = $conf['peername'];
		$this->label = $conf['label'];
		$this->value = $conf['value'];
	}

	public function getValues() {

		$map = call_user_func(array($this->peername, 'getPhpNameMap'));

		$c = new Criteria();
		$c->clearSelectColumns();

		foreach (array($this->label, $this->value) as $arr) {
			foreach ($arr['members'] as $member) {
				if (is_array($member)) {
					foreach ($member as $member) {
						$c->addSelectColumn(constant($this->peername . '::' . $map[$member]));
					}
				} else {
					$c->addSelectColumn(constant($this->peername . '::' . $map[$member]));
				}
			}
		}

		$rs = AuthorPeer::doSelectRs($c);
		$rs->setFetchmode(ResultSet::FETCHMODE_ASSOC);
		while ($rs->next()) {
			$row = $rs->getRow();
			foreach (array('label', 'value') as $key) {
				$arr = $this->$key;
				$params = array($arr['mask']);
				foreach ($arr['members'] as $member) {
					if (is_array($member)) {
						foreach ($member as $member) {
							$field_name = strtolower($map[$member]); // TODO is this always true?
							$params[] = $row[$field_name];
						}
					} else {
						$field_name = strtolower($map[$member]); // TODO is this always true?
						$params[] = $row[$field_name];
					}
				}
				$$key = call_user_func_array('sprintf', $params);
				$tmp[$key] = $$key;
			}
			$result[] = $tmp;
		}

		return $result;
	}
}

class patForms_Creator_Definition {

	static function create($definition, $object = null) {

		$form = patForms::createForm(null, array(
			'name' => $definition->name
		));

		foreach ($definition->elements as $el) {
			$element = &$form->createElement($el['name'], $el['type'], null);
			if (!empty($el['attributes']['datasource'])) {
				$ds = $el['attributes']['datasource'];
				unset($el['attributes']['datasource']);
				$element->setDatasource(new $ds['name']($ds));
			}
			// patForms will choke when we try to set attributes that
			// don't exist for an element type. So we'll have to ask.
			foreach ($el['attributes'] as $name => $value) {
				if ($element->hasAttribute($name)) {
					$element->setAttribute($name, $value);
				}
			}
			$form->addElement($element);
		}
		if (!is_null($object)) {
			$form->setValues($object->toArray());
		}
		if ($definition->autoValidate) {
			$form->setAutoValidate($definition->autoValidate);
		}

		return	$form;
	}

}
?>