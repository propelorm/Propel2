<?

class patForms_Definition {

	private $data = array();

	public function __construct($name, $autoValidate = '') {

		$this->data['name'] = $name;
		$this->data['mtime'] = time();
		if ($autoValidate) {
			$this->data['autoValidate'] = $autoValidate;
		}
	}

	static public function create($conf) {
		// TODO
	}

	public function __get($name) {

		if (isset($this->data[$name])) {
			return $this->data[$name];
		}
	}

	// TODO change protocol to addElement(array $element)
	public function addElement($name, $type, $attributes = array(), $rules = array()) {

		if (is_array($type)) {
			extract($type);
		}

		$this->data['elements'][$name]['name'] = $name;
		$this->data['elements'][$name]['type'] = $type;

		foreach ($attributes as $key => $value) {
			$value = $this->cast($value);
			$this->data['elements'][$name]['attributes'][$key] = $value;
		}
		foreach ($rules as $key => $rule) {
			$this->data['elements'][$name]['rules'][$key] = $rule;
		}
	}

	public function load($filename) {

		$data = $this->read($filename);

		foreach ($data as $key => $value) {
			if ($key == 'elements') {
				foreach ($value as $name => $element) {
					$this->addElement($name, $element);
				}
			} else {
				$this->data[$key] = $this->cast($value);
			}
		}
	}

	public function save($filename) {

		$this->write($filename, $this->data);
	}

	protected function read($filename) {

		$xml = file_get_contents($filename);
		$unserializer = new XML_Unserializer();
		$unserializer->unserialize($xml);
		return $unserializer->getUnserializedData();
	}

	protected function write($filename, $data) {

		$serializer = new XML_Serializer(array (
			'addDecl' => true,
			'encoding' => 'ISO-8859-1',
			'indent' => '  ',
			'rootName' => 'form',
			'defaultTagName' => 'tag'
		));
		$serializer->serialize($data);
		$xml = $serializer->getSerializedData();

		$fp = fopen($filename, 'w+');
		fputs($fp, $xml);
		fclose($fp);
	}

	protected function cast($value) {

		return $value;

		// seems as if patForms_Element(s) are broken here
		// e.g. in patForms_Element_Text::serializeHtmlDefault()
		// at line 245 if ( $this->attributes['display'] == 'no' )
		// will result to true if the display attribute is set
		// to (php boolean) true
		// so casting the 'true'/'false' and 'yes'/'no' values
		// would break intended behaviour here

		if (is_array($value) OR is_bool($value)) {
			return $value;
		}
		if ($value === 'true') {
			return true;
		}
		if ($value === 'false') {
			return false;
		}
		if (preg_match('/^[+-]?[0-9]+$/', $value)) {
			settype($value, 'int');
			return $value;
		}
		if (preg_match('/^[+-]?[0-9]*\.[0-9]+$/', $value)) {
			settype($value, 'double');
			return $value;
		}
		return $value;
	}
}
