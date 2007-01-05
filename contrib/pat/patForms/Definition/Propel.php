<?

class patForms_Definition_Propel extends patForms_Definition {

	private static $creoleTypeMap = array(
		CreoleTypes::BOOLEAN    	=> 'Switch',	// BOOLEAN 			= 1;
		CreoleTypes::BIGINT     	=> 'String',	// BIGINT 			= 2;
		CreoleTypes::SMALLINT   	=> 'String',	// SMALLINT 		= 3;
		CreoleTypes::TINYINT    	=> 'String',	// TINYINT 			= 4;
		CreoleTypes::INTEGER    	=> 'String',	// INTEGER 			= 5;
		CreoleTypes::CHAR       	=> 'String',	// CHAR 			= 6;
		CreoleTypes::VARCHAR    	=> 'String',	// VARCHAR 			= 7;
		CreoleTypes::FLOAT      	=> 'String',	// FLOAT 			= 8;
		CreoleTypes::DOUBLE     	=> 'String',	// DOUBLE 			= 9;
		CreoleTypes::DATE       	=> 'String',		// DATE 			= 10;
		CreoleTypes::TIME       	=> 'String',	// TIME 			= 11;
		CreoleTypes::TIMESTAMP  	=> 'Date',		// TIMESTAMP 		= 12;
		CreoleTypes::VARBINARY  	=> 'String',	// VARBINARY 		= 13;
		CreoleTypes::NUMERIC    	=> 'String',	// NUMERIC 			= 14;
		CreoleTypes::BLOB       	=> 'Text',		// BLOB 			= 15;
		CreoleTypes::CLOB       	=> 'Text',		// CLOB 			= 16;
		CreoleTypes::TEXT       	=> 'Text',		// TEXT 			= 17;
		CreoleTypes::LONGVARCHAR	=> 'Text',		// LONGVARCHAR 		= 17;
		CreoleTypes::DECIMAL    	=> 'String',	// DECIMAL 			= 18;
		CreoleTypes::REAL       	=> 'String',	// REAL 			= 19;
		CreoleTypes::BINARY     	=> 'String',	// BINARY 			= 20;
		CreoleTypes::LONGVARBINARY	=> 'Text',		// LONGVARBINARY 	= 21;
		CreoleTypes::YEAR       	=> 'String',	// YEAR 			= 22;
		CreoleTypes::ARR   	    	=> 'String',
		CreoleTypes::OTHER      	=> 'String'
	);

	private static $validatorTypeMap = array(
		'unique' 		=> null,
		'minLength' 	=> 'patForms_Rule_MinLength',
		'maxLength' 	=> 'patForms_Rule_MaxLength',
		'minValue' 		=> 'patForms_Rule_MinValue',
		'maxValue' 		=> 'patForms_Rule_MaxValue',
		'match'			=> 'patForms_Rule_Match',
		'notMatch'		=> 'patForms_Rule_NotMatch',
		'required' 		=> null, // will be done by the elements "required" attribute
		'validValues'	=> 'patForms_Rule_ValidValues',
	);

	/**
	 * @param      array $conf an assoc array of parameters. these are:
	 *     - string name => $name of the propel object class
	 *     - string filename => $filename of the form definition xml file
	 */

	static public function create($conf) {

		extract($conf);

		$autoValidate = isset($autoValidate) ? $autoValidate : 'save';

		$definition = new patForms_Definition_Propel($name, $autoValidate);

		if (0 AND file_exists($filename)) {
			// load definition from xml file
			$definition->load($filename);
		} else {
			// populate definition from table map and save it to xml file
			$definition = self::populateFromTableMap($definition, $conf);
			$definition->save($filename);
		}

		return $definition;
	}

	private function populateFromTableMap($definition, $conf) {

		extract($conf);

		$mapBuilder = call_user_func(array($name . 'Peer', 'getMapBuilder'));
		$tablename = constant($name . 'Peer::TABLE_NAME');
		$tableMap = $mapBuilder->getDatabaseMap()->getTable($tablename);
		$cols = $tableMap->getColumns();

		foreach ($cols as $col) {

			$phpname = $col->getPhpName();
			// this would need a patched version of patForms in order
			// to retrieve request vars after having submitted the form
			// TODO - ask patForms developers to enable this
			// $elementName = $tablename . '[' . $phpname . ']';
			$elementName = $phpname;

			$elementType = self::$creoleTypeMap[$col->getCreoleType()];

			// TODO somehow retrieve element type specific default values?
			$elementAttributes = array(
				'name'  => $elementName,
				'title' => $phpname,
				'label' => $phpname,
				'description' => $phpname,
				'edit'  => 'yes',
				'display' => $col->isPrimaryKey() ? 'no' : 'yes',
				// Is the element required?
				// TODO Can we retrieve this info from the Column object?
				'required'  => true,
			);

			switch ($col->getCreoleType()) {
				case CreoleTypes::BOOLEAN: {
					$elementAttributes['value'] = 1;
					break;
				}
				case CreoleTypes::DATE: {
					// TODO doesn't seem to work for some reason
					// $elementAttributes['format'] = 'date';
					// $elementAttributes['dateformat'] = 'Ymd';
					break;
				}
			}

			if ($col->isForeignKey()) {

				$relColname = $col->getRelatedColumnName();
				$relTablename = $col->getRelatedTableName();
				$relColPhpname =
					Propel::getDatabaseMap(constant($relTablename . 'Peer::DATABASE_NAME'))->
					getTable($relTablename)->getColumn($relColname)->getPhpname();

				$elementAttributes['datasource'] = array (
					'name' => 'patForms_Datasource_Propel',
					'peername' => $relTablename . 'Peer',
					'label' => array(
						'initial' => 'Please select one ...',
						'members' => array($relColPhpname),
						'mask' => '%s',
					),
					'value' => array(
						'members' => array($relColPhpname),
						'mask' => '%s',
					),
				);
				$elementType = 'Enum';
			}

			$rules = array();
			if ($col->hasValidators()) {
				foreach ($col->getValidators() as $validator) {
					$name = $validator->getName();
					$type = self::$validatorTypeMap[$name];
					if (!is_null($type)) {
						$rules[$name] = array (
							'table' => $col->getTablename(),
							'col' => $col->getColumnName(),
							'name' => $name,
							'type' => self::$validatorTypeMap[$name],
							'value' => $validator->getValue(),
							'class' => $validator->getClass(),
							'message' => $validator->getMessage(),
						);
					}
				}
			}

			$definition->addElement($phpname, $elementType, $elementAttributes, $rules);
		}

		return $definition;
	}
}
