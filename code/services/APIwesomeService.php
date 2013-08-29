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

	public function retrieve($object, $output) {

		// Grab all visible data objects of the specified type.

		$objects = $this->retrieveValidated($object);

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
	 *	Return all visible data objects of the specified type if valid.
	 *
	 *	@parameter string
	 *	@return array
	 */

	public function retrieveValidated($class) {

		// Make sure at least one data object and configuration exist, otherwise this request is considered invalid.

		if(in_array($class, ClassInfo::subclassesFor('DataObject'))) {
			$object = DataObject::get_one($class, '', true, 'APIwesomeVisibility DESC');
			$configuration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($class) . "'");
			if($object && $configuration) {

				// Retrieve the attributes for this data object.

				$columns = DataObject::database_fields($class);
				array_shift($columns);
				$visibility = $object->APIwesomeVisibility ? explode(',', $object->APIwesomeVisibility) : null;

				// Construct the select statement, including any visibility customisation.

				$select = '';
				$iteration = 0;
				foreach($columns as $name => $type) {

					// Take the visibility attribute into account.

					if($name !== 'APIwesomeVisibility') {
						if(is_array($visibility) && isset($visibility[$iteration]) && $visibility[$iteration]) {
							$select .= $name . ', ';
						}
						$iteration++;
					}
				}
				$select = rtrim($select, ', ');

				// Make sure we have a valid select statement.

				if($select) {

					// Retrieve the list of corresponding data objects, including any visibility customisation.

					$query = new SQLQuery("ClassName, $select, ID", array($class));
					$query = $query->execute();
					$objects = array();
					foreach($query as $temporary) {

						// Remove null attributes.

						$object = array();
						foreach($temporary as $attribute => $value) {
							if($value) {
								$object[$attribute] = $value;
							}
						}
						$objects[] = $object;
					}

					// Return our list of validated data objects.

					return $objects;
				}
			}
		}

		// The data object was not valid.

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

	public function retrieveJSON($objects, $visibility = false, $addHeader = false, $callback = false) {

		// Convert the input array to JSON.
		
		$JSON = array();
		foreach($objects as $temporary) {

			// Remove attributes that are not required, while recursively retrieving data object relationships.

			$JSON[] = array($temporary['ClassName'] => $this->recursiveRelationships($temporary, $visibility));
		}
		$JSON = Convert::array2json(array('DataObjectList' => $JSON));

		// Apply the callback function from configuration.

		if($callback) {
			$configuration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($objects[0]['ClassName']) . "'");
			$JSON = $configuration->CallbackFunction ? str_replace(' ', '_', $configuration->CallbackFunction) . "($JSON);" : $JSON;
		}

		// Set the response header, and return the JSON.

		if($addHeader) {
			($callback && $configuration->CallbackFunction) ? Controller::curr()->getResponse()->addHeader('Content-Type', 'application/javascript') : Controller::curr()->getResponse()->addHeader('Content-Type', 'application/json');
		}
		return $JSON;
	}

	/**
	 *	Recursively return the relationships for a given data object map.
	 */

	private function recursiveRelationships(&$temporary, $visibility = false, $cache = array()) {

		$object = array();

		// Add this class and ID to the cache, such that we don't recurse infinitely.

		if(!in_array("{$temporary['ClassName']} {$temporary['ID']}", $cache)) {
			$cache[] = "{$temporary['ClassName']} {$temporary['ID']}";
			foreach($temporary as $attribute => $value) {
				if(($attribute !== 'ClassName') && ($attribute !== 'APIwesomeVisibility') && ($attribute !== 'RecordClassName')) {

					// Update the attribute name if a relationship is found.

					$relationship = ((strlen($attribute) > 2) && (substr($attribute, strlen($attribute) - 2) === 'ID')) ? substr($attribute, 0, -2) : null;

					if($relationship) {
						$relationObject = DataObject::get_by_id($temporary['ClassName'], $temporary['ID'])->$relationship();
						$relationVisibility = $relationObject->APIwesomeVisibility ? explode(',', $relationObject->APIwesomeVisibility) : null;
						$map = $relationObject->toMap();
						$select = $map;

						// Construct the output, including any visibility customisation.

						if($visibility) {
							$select = array('ClassName' => $relationObject->ClassName, 'ID' => $relationObject->ID);
							$iteration = 0;
							foreach($map as $name => $output) {

								// Take the visibility attribute into account.

								if(($name !== 'ClassName') && ($name !== 'APIwesomeVisibility')) {
									if(is_array($relationVisibility) && isset($relationVisibility[$iteration]) && $relationVisibility[$iteration]) {
										$select[$name] = $output;
									}
									$iteration++;
								}
							}
						}

						// Make sure there isn't another level of recursion available.

						$object[$relationship] = array($relationObject->ClassName => $this->recursiveRelationships($select, $visibility, $cache));
					}
					else {
						$object[$attribute] = $value;
					}
				}
			}
		}
		else {

			// If we have already returned this data object with the same ID.

			$object['ID'] = $temporary['ID'];
		}

		// Return the attributes from this level of recursion.

		return $object;
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

	public function retrieveXML($objects, $visibility = false, $addHeader = false) {

		$output = array();
		foreach($objects as $temporary) {
			$output[] = array('ClassName' => $temporary['ClassName'], 'Object' => $this->recursiveRelationships($temporary, $visibility));
		}
		$objects = $output;

		// Convert the input array to XML.

		$XML = new SimpleXMLElement('<DataObjectList/>');
		foreach($objects as $object) {

			// Add the data objects in the correct format, using the data object name as a parent element.

			$objectXML = $XML->addChild($object['ClassName']);
			$this->recursiveXML($objectXML, $object['Object']);
		}

		// Set the response header, and return the XML.

		if($addHeader) {
			Controller::curr()->getResponse()->addHeader('Content-Type', 'application/xml');
		}
		return $XML->asXML();
	}

	/**
	 *	Recursively compose the XML children elements for a given data object map.
	 */

	private function recursiveXML(&$objectXML, $object) {

		foreach($object as $attribute => $value) {

			// Remove attributes that are not required.

			if($attribute !== 'APIwesomeVisibility') {
				if(is_array($value)) {
					foreach($value as $name => $variable) {
						$relationshipXML = $objectXML->addChild($name);
						$this->recursiveXML($relationshipXML, $variable);
					}
				}
				else {
					$objectXML->addChild($attribute, $value);
				}
			}
		}
	}

}
