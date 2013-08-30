<?php

/**
 *	Handles the current request and outputs the appropriate JSON/XML.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeService {

	/**
	 *	Retrieve the appropriate JSON/XML output of a specified data object type.
	 *
	 *	@parameter string
	 *	@parameter string
	 *	@return JSON/XML
	 */

	public function retrieve($class, $output) {

		// Grab all visible data objects of the specified type.

		$objects = $this->retrieveValidated($class);

		// Return the appropriate JSON/XML output of these data objects.

		if($objects) {
			$output = strtoupper($output);
			if($output === 'JSON') {
				return $this->retrieveJSON($objects, true, true, true);
			}
			else if($output === 'XML') {
				return $this->retrieveXML($objects, true, true);
			}
		}

		// The current request was not valid.

		return Controller::curr()->httpError(404);
	}

	/**
	 *	Return all data object visible attributes of the specified type.
	 *
	 *	@parameter string
	 *	@return array
	 */

	public function retrieveValidated($class) {

		// Make sure this data object type has visibility customisation.

		if(in_array($class, ClassInfo::subclassesFor('DataObject')) && DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($class) . "'") && ($object = DataObject::get_one($class, '', true, 'APIwesomeVisibility DESC'))) {
			$visibility = $object->APIwesomeVisibility ? explode(',', $object->APIwesomeVisibility) : null;
			if($visibility && in_array('1', $visibility)) {

				// Grab the appropriate attributes for this data object.

				$columns = DataObject::database_fields($class);
				array_shift($columns);

				// Apply any visibility customisation.

				$select = '';
				$iteration = 0;
				foreach($columns as $attribute => $type) {
					if($attribute !== 'APIwesomeVisibility') {
						if(isset($visibility[$iteration]) && $visibility[$iteration]) {
							$select .= $attribute . ', ';
						}
						$iteration++;
					}
				}

				// Grab all data object visible attributes.

				$query = new SQLQuery("ClassName, {$select}ID", array($class));
				$objects = array();
				foreach($query->execute() as $temporary) {

					// Return an array of data object maps.

					$object = array();
					foreach($temporary as $attribute => $value) {
						if($value) {
							$object[$attribute] = $value;
						}
					}
					$objects[] = $object;
				}
				return $objects;
			}
		}

		// The specified data object type had no visibility customisation.

		return null;
	}

	/**
	 *	Compose the appropriate JSON output for the corresponding array of data objects.
	 *	NOTE: DataList->toNestedArray();
	 *
	 *	@parameter array
	 *	@parameter boolean
	 *	@parameter boolean
	 *	@parameter boolean
	 *	@return JSON
	 */

	public function retrieveJSON($objects, $attributeVisibility = false, $contentHeader = false, $callback = false) {

		// Convert the corresponding array of data objects to JSON.
		
		$JSON = array();
		foreach($objects as $object) {

			// Compose the appropriate output for all relationships.

			$JSON[] = array($object['ClassName'] => $this->recursiveRelationships($object, $attributeVisibility));
		}
		$JSON = Convert::array2json(array('DataObjectList' => $JSON));

		// Apply a defined javascript callback function.

		$header = false;
		if($callback && ($configuration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($objects[0]['ClassName']) . "'"))) {
			if($configuration->CallbackFunction) {
				$JSON = str_replace(' ', '_', $configuration->CallbackFunction) . "($JSON);";

				// Apply a javascript content response header.

				if($contentHeader) {
					Controller::curr()->getResponse()->addHeader('Content-Type', 'application/javascript');
					$header = true;
				}
			}
		}

		// Apply a content response header and return the composed JSON.

		if($contentHeader && !$header) {
			Controller::curr()->getResponse()->addHeader('Content-Type', 'application/json');
		}
		return $JSON;
	}

	/**
	 *	Recursively return the relationships for a given data object map.
	 */

	private function recursiveRelationships(&$object, $attributeVisibility = false, $cache = array()) {

		$output = array();

		// Add this class and ID to the cache, such that we don't recurse infinitely.

		if(!in_array("{$object['ClassName']} {$object['ID']}", $cache)) {
			$cache[] = "{$object['ClassName']} {$object['ID']}";
			foreach($object as $attribute => $value) {
				if(($attribute !== 'ClassName') && ($attribute !== 'APIwesomeVisibility') && ($attribute !== 'RecordClassName')) {

					// Update the attribute name if a relationship is found.

					$relationship = ((strlen($attribute) > 2) && (substr($attribute, strlen($attribute) - 2) === 'ID')) ? substr($attribute, 0, -2) : null;

					if($relationship) {
						$relationObject = DataObject::get_by_id($object['ClassName'], $object['ID'])->$relationship();
						$relationVisibility = $relationObject->APIwesomeVisibility ? explode(',', $relationObject->APIwesomeVisibility) : null;
						$map = $relationObject->toMap();
						$select = $map;

						// Construct the output, including any visibility customisation.

						if($attributeVisibility) {
							$select = array('ClassName' => $relationObject->ClassName, 'ID' => $relationObject->ID);
							$iteration = 0;
							foreach($map as $name => $content) {

								// Take the visibility attribute into account.

								if(($name !== 'ClassName') && ($name !== 'APIwesomeVisibility')) {
									if(is_array($relationVisibility) && isset($relationVisibility[$iteration]) && $relationVisibility[$iteration]) {
										$select[$name] = $content;
									}
									$iteration++;
								}
							}
						}

						// Make sure there isn't another level of recursion available.

						$output[$relationship] = array($relationObject->ClassName => $this->recursiveRelationships($select, $attributeVisibility, $cache));
					}
					else {
						$output[$attribute] = $value;
					}
				}
			}
		}
		else {

			// If we have already returned this data object with the same ID.

			$output['ID'] = $object['ID'];
		}

		// Return the attributes from this level of recursion.

		return $output;
	}

	/**
	 *	Compose the appropriate XML output for the corresponding array of data objects.
	 *	NOTE: DataList->toNestedArray();
	 *
	 *	@parameter array
	 *	@parameter boolean
	 *	@parameter boolean
	 *	@return XML
	 */

	public function retrieveXML($objects, $attributeVisibility = false, $contentHeader = false) {

		// Convert the corresponding array of data objects to XML.

		$XML = new SimpleXMLElement('<DataObjectList/>');
		foreach($objects as $object) {

			// Compose the appropriate output for all relationships.

			$objectXML = $XML->addChild($object['ClassName']);
			$this->recursiveXML($objectXML, $this->recursiveRelationships($object, $attributeVisibility));
		}

		// Apply a content response header and return the composed XML.

		if($contentHeader) {
			Controller::curr()->getResponse()->addHeader('Content-Type', 'application/xml');
		}
		return $XML->asXML();
	}

	/**
	 *	Recursively compose the XML children elements for a given data object map.
	 */

	private function recursiveXML(&$parentXML, $object) {

		foreach($object as $attribute => $value) {

			// Remove attributes that are not required.

			if($attribute !== 'APIwesomeVisibility') {
				if(is_array($value)) {
					foreach($value as $name => $variable) {
						$relationshipXML = $parentXML->addChild($name);
						$this->recursiveXML($relationshipXML, $variable);
					}
				}
				else {
					$parentXML->addChild($attribute, $value);
				}
			}
		}
	}

}
