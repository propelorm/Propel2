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

		if (isset($this->label['initial']) OR isset($this->value['initial'])) {
			$label = isset($this->label['initial']) ? $this->label['initial'] : '';
			$value = isset($this->value['initial']) ? $this->value['initial'] : '';
			$result[] = array(
				'value' => $value,
				'label' => $label
			);
		}

		$rs = AuthorPeer::doSelectStmt($c);
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
