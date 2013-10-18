<?php

/**
 *	Handles the current request and outputs the appropriate JSON/XML.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeService {

	/**
	 *	Retrieve the appropriate JSON/XML output of a specified data object type.
	 *
	 *	@parameter <{DATA_OBJECT_NAME}> string
	 *	@parameter <{OUTPUT_TYPE}> string
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
	 *	@parameter <{DATA_OBJECT_NAME}> string
	 *	@return array
	 */

	public function retrieveValidated($class) {

		// Make sure this data object type has visibility customisation.

		if(in_array($class, ClassInfo::subclassesFor('DataObject')) && ($configuration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($class) . "'")) && DataObject::get_one($class)) {
			$visibility = $configuration->APIwesomeVisibility ? explode(',', $configuration->APIwesomeVisibility) : null;
			if($visibility && in_array('1', $visibility)) {

				// Grab the appropriate attributes for this data object.

				$columns = DataObject::database_fields($class);
				array_shift($columns);

				// Apply any visibility customisation.

				$select = '';
				$iteration = 0;
				foreach($columns as $attribute => $type) {
					if(isset($visibility[$iteration]) && $visibility[$iteration]) {
						$select .= $attribute . ', ';
					}
					$iteration++;
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
	 *	@parameter <{DATA_OBJECTS}> array
	 *	@parameter <{USE_ATTRIBUTE_VISIBILITY}> boolean
	 *	@parameter <{SET_CONTENT_HEADER}> boolean
	 *	@parameter <{WRAP_JAVASCRIPT_CALLBACK}> boolean
	 *	@return JSON
	 */

	public function retrieveJSON($objects, $attributeVisibility = false, $contentHeader = false, $callback = false) {

		// Convert the corresponding array of data objects to JSON.
		
		$temporary = array();
		foreach($objects as $object) {

			// Compose the appropriate output for all relationships.

			$temporary[] = array($object['ClassName'] => $this->recursiveRelationships($object, $attributeVisibility));
		}
		$JSON = Convert::array2json(array('DataObjectList' => $temporary));

		// Apply a defined javascript callback function.

		$header = false;
		if($callback && ($configuration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($objects[0]['ClassName']) . "'"))) {
			if($configuration->CallbackFunction) {
				$JSON = str_replace(' ', '_', $configuration->CallbackFunction) . "({$JSON});";

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

		// Cache relationship data objects to prevent infinite recursion.

		if(!in_array("{$object['ClassName']} {$object['ID']}", $cache)) {
			$cache[] = "{$object['ClassName']} {$object['ID']}";
			foreach($object as $attribute => $value) {
				if(($attribute !== 'ClassName') && ($attribute !== 'RecordClassName')) {

					// Grab the name of a relationship.

					$relationship = ((substr($attribute, strlen($attribute) - 2) === 'ID') && (strlen($attribute) > 2)) ? substr($attribute, 0, -2) : null;
					if($relationship && ($value != 0)) {

						// Grab the relationship.

						$relationObject = DataObject::get_by_id($object['ClassName'], $object['ID'])->$relationship();
						if($attributeVisibility) {

							// Grab the attribute visibility.

							$relationConfiguration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($relationObject->ClassName) . "'");
							$relationVisibility = ($relationConfiguration && $relationConfiguration->APIwesomeVisibility) ? explode(',', $relationConfiguration->APIwesomeVisibility) : null;
							if($relationVisibility && in_array('1', $relationVisibility)) {
								$temporaryMap = $relationObject->toMap();
								$columns = DataObject::database_fields($relationObject->ClassName);
								$map = array();
								foreach($columns as $column => $type) {
									$map[$column] = isset($temporaryMap[$column]) ? $temporaryMap[$column] : null;
								}
							}
							else {
								$output[$relationship] = array($relationObject->ClassName => array('ID' => $relationObject->ID));
								continue;
							}

							// Grab all data object visible attributes.

							$select = array('ClassName' => $relationObject->ClassName, 'ID' => $relationObject->ID);
							$iteration = 0;
							foreach($map as $relationshipAttribute => $relationshipValue) {
								if($relationshipAttribute !== 'ClassName') {
									if(isset($relationVisibility[$iteration]) && $relationVisibility[$iteration]) {
										if(!is_null($relationshipValue)) {
											$select[$relationshipAttribute] = is_integer($relationshipValue) ? (string)$relationshipValue : $relationshipValue;
										}
									}
									$iteration++;
								}
							}
						}
						else {
							$select = $map;
						}

						// Check the corresponding relationship.

						$output[$relationship] = array($relationObject->ClassName => $this->recursiveRelationships($select, $attributeVisibility, $cache));
					}
					else if(!$relationship) {
						$output[$attribute] = is_integer($value) ? (string)$value : $value;
					}
				}
			}
		}
		else {

			// This relationship has previously been cached.

			$output['ID'] = $object['ID'];
		}

		// Return the visible relationship attributes.

		return $output;
	}

	/**
	 *	Compose the appropriate XML output for the corresponding array of data objects.
	 *	NOTE: DataList->toNestedArray();
	 *
	 *	@parameter <{DATA_OBJECTS}> array
	 *	@parameter <{USE_ATTRIBUTE_VISIBILITY}> boolean
	 *	@parameter <{SET_CONTENT_HEADER}> boolean
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

			// Convert a corresponding relationship to an XML child element.

			if(is_array($value)) {
				foreach($value as $relationshipAttribute => $relationshipValue) {
					$relationshipXML = $parentXML->addChild($relationshipAttribute);

					// Check the corresponding relationship.

					$this->recursiveXML($relationshipXML, $relationshipValue);
				}
			}
			else {
				$parentXML->$attribute = $value;
			}
		}
	}

	/**
	 *	Parse the corresponding APIwesome JSON input, returning a formatted array of data objects and relationships.
	 *
	 *	@parameter <{APIWESOME_JSON}> JSON
	 *	@return array
	 */

	public function parseJSON($JSON) {
		
		// Convert the corresponding JSON to a formatted array of data objects.

		$temporary = Convert::json2array($JSON);
		$objects = isset($temporary['DataObjectList']) ? $temporary['DataObjectList'] : null;
		return $objects;
	}

	/**
	 *	Parse the corresponding APIwesome XML input, returning a formatted array of data objects and relationships.
	 *
	 *	@parameter <{APIWESOME_XML}> XML
	 *	@return array
	 */

	public function parseXML($XML) {

		// Convert the corresponding XML to a formatted array of data objects.

		$temporary = (strpos($XML, '<DataObjectList>') && strrpos($XML, '</DataObjectList>')) ? $this->recursiveXMLArray(new SimpleXMLElement($XML)) : null;
		if($temporary) {

			// Compose a format similar to that of the JSON.

			$objects = array();
			foreach($temporary as $class => $value) {
				foreach($value as $object) {
					$objects[] = array($class => $object);
				}
			}
		}
		return $objects;
	}

	/**
	 *	Recursively compose an array for the given XML children elements.
	 */

	private function recursiveXMLArray($XML, $objects = array()) {
		foreach((array)$XML as $attribute => $value) {
			$objects[$attribute] = (is_object($value) || is_array($value)) ? $this->recursiveXMLArray($value) : $value;
		}
		return $objects;
	}

}
