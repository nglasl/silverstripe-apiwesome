<?php

/**
 *	Handles the current request and outputs the appropriate JSON/XML, while providing any additional functionality.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeService {

	/**
	 *	Attempt to create a hash for a new security token.
	 *
	 *	@parameter <{EMPTY_KEY_VARIABLE}> null
	 *	@parameter <{HASH_ITERATION_COUNT}> integer
	 *	@parameter <{KEY_CHARACTER_OUTPUT_COUNT}> integer
	 *	@return string/boolean
	 */

	public function generateHash(&$key = null, $iterations = 14, $characters = 128) {

		// Temporarily store the key for something such as a session.

		$key = bin2hex(openssl_random_pseudo_bytes($characters / 2));
		$salt = substr(str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(18))), 0, 22);
		$hash = crypt($key, '$2a$' . $iterations . '$' . $salt);
		return (strlen($hash) >= 13) ? $hash : false;
	}

	/**
	 *	Retrieve the appropriate JSON/XML output of a specified data object type, with optional filters.
	 *
	 *	@parameter <{DATA_OBJECT_NAME}> string
	 *	@parameter <{OUTPUT_TYPE}> string
	 *	@parameter <{LIMIT}> integer
	 *	@parameter <{FILTER}> array(string, string)
	 *	@parameter <{SORT}> array(string, string)
	 *	@return JSON/XML
	 */

	public function retrieve($class, $output, $limit = null, $filter = null, $sort = null) {

		// Grab all visible data objects of the specified type.

		$objects = $this->retrieveValidated($class, $limit, $filter, $sort);

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
	 *	Return all data object visible attributes of the specified type, with optional filters.
	 *
	 *	@parameter <{DATA_OBJECT_NAME}> string
	 *	@parameter <{LIMIT}> integer
	 *	@parameter <{FILTER}> array(string, string)
	 *	@parameter <{SORT}> array(string, string)
	 *	@return array
	 */

	public function retrieveValidated($class, $limit = null, $filter = null, $sort = null) {

		// Make sure this data object type has visibility customisation.

		if(in_array($class, ClassInfo::subclassesFor('DataObject')) && ($configuration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($class) . "'")) && DataObject::get_one($class)) {
			$visibility = $configuration->APIwesomeVisibility ? explode(',', $configuration->APIwesomeVisibility) : null;
			if($visibility && in_array('1', $visibility)) {

				// Grab the appropriate attributes for this data object.

				$where = array();
				if($class === 'Image') {
					$class = 'File';
					$where[] = "ClassName = 'Image'";
				}
				$columns = DataObject::database_fields($class);
				array_shift($columns);

				// Make sure the filter and sort are valid.

				$filterValid = false;
				if(is_array($filter) && (count($filter) === 2)) {
					if(!isset($columns[$filter[0]]) && ($filter[0] !== 'ID') && ($filter[0] !== 'ClassName')) {
						return null;
					}
					$filterValid = true;
				}
				if(is_array($sort) && (count($sort) === 2)) {
					$sort[1] = strtoupper($sort[1]);
					if(!isset($columns[$sort[0]]) && ($sort[0] !== 'ID') && ($sort[0] !== 'ClassName') || (($sort[1] !== 'ASC') && ($sort[1] !== 'DESC'))) {
						return null;
					}
					$sort = Convert::raw2sql($sort[0]) . ' ' . Convert::raw2sql($sort[1]);
				}
				else {
					$sort = array();
				}

				// Apply any visibility customisation.

				$select = '';
				$filterApplied = false;
				$iteration = 0;
				foreach($columns as $attribute => $type) {
					if(isset($visibility[$iteration]) && $visibility[$iteration]) {
						$select .= $attribute . ', ';
						if($filterValid && !$filterApplied && (($filter[0] === $attribute) || ($filter[0] === 'ID') || ($filter[0] === 'ClassName'))) {

							// Apply the filter if the matching attribute is visible.

							$filterApplied = true;
							$where[] = Convert::raw2sql($filter[0]) . " = '" . Convert::raw2sql($filter[1]) . "'";
						}
					}
					$iteration++;
				}
				if($filterValid && !$filterApplied) {
					return null;
				}

				// Grab all data object visible attributes.

				$query = new SQLQuery("ClassName, {$select}ID", $class, $where, $sort, array(), array(), (is_numeric($limit) ? $limit : array()));
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
						$temporaryMap = $relationObject->toMap();
						if($attributeVisibility) {

							// Grab the attribute visibility.

							$relationConfiguration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($relationObject->ClassName) . "'");
							$relationVisibility = ($relationConfiguration && $relationConfiguration->APIwesomeVisibility) ? explode(',', $relationConfiguration->APIwesomeVisibility) : null;
							if($relationVisibility && in_array('1', $relationVisibility)) {
								$columns = DataObject::database_fields(($relationObject->ClassName === 'Image') ? 'File' : $relationObject->ClassName);
								$map = array();
								foreach($columns as $column => $type) {
									$map[$column] = isset($temporaryMap[$column]) ? $temporaryMap[$column] : null;
								}
							}
							else {
								$output[$relationship] = array($relationObject->ClassName => array('ID' => (string)$relationObject->ID));
								continue;
							}

							// Grab all data object visible attributes.

							$select = array('ClassName' => $relationObject->ClassName, 'ID' => $relationObject->ID);
							$iteration = 0;
							foreach($map as $relationshipAttribute => $relationshipValue) {
								if($relationshipAttribute !== 'ClassName') {
									if(isset($relationVisibility[$iteration]) && $relationVisibility[$iteration]) {
										if(!is_null($relationshipValue)) {

											// Compose any asset file paths.

											$select[$relationshipAttribute] = (((strpos(strtolower($relationshipAttribute), 'file') !== false) || (strpos(strtolower($relationshipAttribute), 'image') !== false)) && (strpos($relationshipValue, 'assets/') !== false)) ? Director::absoluteURL($relationshipValue) : (is_integer($relationshipValue) ? (string)$relationshipValue : $relationshipValue);
										}
									}
									$iteration++;
								}
							}
						}
						else {
							$select = $temporaryMap;
						}

						// Check the corresponding relationship.

						$output[$relationship] = array($relationObject->ClassName => $this->recursiveRelationships($select, $attributeVisibility, $cache));
					}
					else if(!$relationship) {

						// Compose any asset file paths.

						$output[$attribute] = (((strpos(strtolower($attribute), 'file') !== false) || (strpos(strtolower($attribute), 'image') !== false)) && (strpos($value, 'assets/') !== false)) ? Director::absoluteURL($value) : (is_integer($value) ? (string)$value : $value);
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
				$relationship = $parentXML->addChild($attribute);
				foreach($value as $relationshipAttribute => $relationshipValue) {
					$relationshipXML = $relationship->addChild($relationshipAttribute);

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
	 *	Return the appropriate staged JSON/XML output for the corresponding page.
	 *
	 *	@parameter <{PAGE_ID}> integer
	 *	@parameter <{OUTPUT_TYPE}> string
	 *	@URLparameter s <{STAGE_OR_LIVE}> string
	 *	@return JSON/XML
	 */

	public function retrieveStaged($page, $output) {

		// Bypass any staging preview restrictions where required.

		$request = Controller::curr()->getRequest();
		$stage = $request->getVar('s') ? $request->getVar('s') : $request->getVar('stage');
		$output = strtoupper($output);
		if((($stage === 'Stage') || ($stage === 'Live')) && (($output === 'JSON') || ($output === 'XML'))) {

			// Set the appropriate staging mode.

			Versioned::reading_stage($stage);

			// Compose the appropriate JSON/XML.

			$function = "retrieve{$output}";
			$temporary = array(
				0 => SiteTree::get_by_id('SiteTree', $page)->toMap()
			);
			return $this->$function($temporary, false, true);
		}

		// The current request was not valid.

		return array();
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
