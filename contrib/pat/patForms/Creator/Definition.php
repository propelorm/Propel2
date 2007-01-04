<?php

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
			if (isset($el['rules'])) {
				foreach ($el['rules'] as $rule) {
					$element->addRule(new $rule['type']($rule));
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
