<?php

/**
 *	Handles the current request and outputs the appropriate JSON/XML, while providing any additional functionality.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class APIwesomeService {

	/**
	 *	These are enabled by default, however will greatly impact performance if many nested relationships are visible.
	 */

	public $recursiveRelationships = true;

	/**
	 *	This is enabled by default, however will slightly impact performance if many nested relationships are visible.
	 */

	public $prettyJSON = true;

	/**
	 *	Attempt to match an existing security token hash, or create a random hash for a new security token.
	 *
	 *	@parameter <{HASH_ITERATION_COUNT}> integer
	 *	@parameter <{KEY_CHARACTER_OUTPUT_COUNT}> integer
	 *	@return array(string, string)/boolean
	 */

	public function generateHash($key = null, $salt = null, $iterations = 12, $characters = 64) {

		if(is_null($key) || is_null($salt)) {
			$key = bin2hex(openssl_random_pseudo_bytes($characters / 2));
			$salt = substr(str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(18))), 0, 22);
		}
		$hash = crypt($key, '$2a$' . $iterations . '$' . $salt);

		// Temporarily store the key and salt for session use.

		return (strlen($hash) >= 13) ? array(
			'key' => $key,
			'salt' => $salt,
			'hash' => $hash
		) : false;
	}

	/**
	 *	Determine whether a token matches the current security token.
	 *
	 *	@parameter <{SECURITY_TOKEN}> string
	 *	@return integer
	 */

	const VALID = 1;
	const INVALID = 2;
	const EXPIRED = 4;

	public function validateToken($token) {

		// Compare the token against the current security token.

		$token = explode(':', $token);
		$currentToken = APIwesomeToken::get()->sort('Created', 'DESC')->first();
		if((count($token) === 2) && ($generation = $this->generateHash($token[0], $token[1])) && $currentToken) {
			$hash = $generation['hash'];
			if($hash === $currentToken->Hash) {

				// The token matches the current security token.

				return self::VALID;
			}

			// Determine whether the token has been invalidated.

			else {
				$tokens = APIwesomeToken::get()->sort('Created', 'DESC');
				foreach($tokens as $token) {
					if($hash === $token->Hash) {

						// The token matches a previous security token.

						return self::EXPIRED;
					}
				}
			}
		}

		// The token does not match a security token.

		return self::INVALID;
	}

	/**
	 *	Retrieve the appropriate JSON/XML output of a specified data object type, with optional filters.
	 *
	 *	@parameter <{DATA_OBJECT_NAME}> string
	 *	@parameter <{OUTPUT_TYPE}> string
	 *	@parameter <{LIMIT}> integer
	 *	@parameter <{SORT}> array(string, string)
	 *	@parameter <{FILTERS}> array
	 *	@return JSON/XML
	 */

	public function retrieve($class, $output, $limit = null, $sort = null, $filters = null) {

		// Grab all visible data objects of the specified type.

		$objects = $this->retrieveValidated($class, $limit, $sort, $filters);

		// Return the appropriate JSON/XML output of these data objects.

		if($objects) {
			$output = strtoupper($output);
			if($output === 'JSON') {
				return $this->retrieveJSON($objects, $filters, true, true, true);
			}
			else if($output === 'XML') {
				return $this->retrieveXML($objects, $filters, true, true);
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
	 *	@parameter <{SORT}> array(string, string)
	 *	@parameter <{FILTERS}> array
	 *	@return array
	 */

	public function retrieveValidated($class, $limit = null, $sort = null, $filters = null) {

		// Validate the data object class.

		$class = strtolower($class);
		if(in_array($class, array_map('strtolower', ClassInfo::subclassesFor('DataObject'))) && ($configuration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', array(
			'LOWER(IsFor) = ?' => $class
		))) && ($temporaryClass = DataObject::get_one($class))) {
			$class = ClassInfo::baseDataClass($temporaryClass->ClassName);
			$visibility = $configuration->APIwesomeVisibility ? explode(',', $configuration->APIwesomeVisibility) : null;

			// Validate the sort and filters.

			$where = array();
			$sortValid = (is_array($sort) && (count($sort) === 2) && ($order = strtoupper($sort[1])) && (($order === 'ASC') || ($order === 'DESC')));
			$filterValid = (is_array($filters) && count($filters));
			$sorting = array();
			$filtering = array();

			// Grab the appropriate attributes for this data object.

			$columns = array();
			$from = array();
			foreach(ClassInfo::subclassesFor($class) as $subclass) {

				// Determine the tables to join.

				$subclassFields = DataObject::database_fields($subclass);
				if(ClassInfo::hasTable($subclass)) {

					// Determine the versioned table.

					$same = ($subclass === $class);
					if($subclass::has_extension('Versioned')) {
						$subclass = "{$subclass}_Live";
					}
					if(!$same) {
						$from[] = $subclass;
					}
				}

				// Prepend the table names.

				$subclassColumns = array();
				foreach($subclassFields as $column => $type) {
					$subclassColumn = "{$subclass}.{$column}";
					$subclassColumns[$subclassColumn] = $type;

					// Determine the tables to sort and filter on.

					if($sortValid && ($sort[0] === $column)) {
						$sorting[] = "{$subclassColumn} {$order}";
					}
					if($filterValid && isset($filters[$column])) {
						$filtering[$subclassColumn] = $filters[$column];
					}
				}
				$columns = array_merge($columns, $subclassColumns);
			}
			array_shift($columns);

			// Determine the versioned table.

			if($class::has_extension('Versioned')) {
				$class = "{$class}_Live";
			}

			// Determine ID based sorting and filtering, as these aren't considered database fields.

			if($sortValid && ($sort[0] === 'ID')) {
				$sorting[] = "{$class}.ID {$order}";
			}
			if($filterValid && isset($filters['ID'])) {
				$where["{$class}.ID = ?"] = $filters['ID'];
			}

			// Make sure this data object type has visibility customisation.

			if($visibility && (count($visibility) === count($columns)) && in_array('1', $visibility)) {

				// Apply any visibility customisation.

				$select = ' ';
				$iteration = 0;
				foreach($columns as $attribute => $type) {
					if(isset($visibility[$iteration]) && $visibility[$iteration]) {
						$select .= $attribute . ', ';
						if(isset($filtering[$attribute])) {

							// Apply the filter if the matching attribute is visible.

							$column = is_numeric($filtering[$attribute]) ? $attribute : "LOWER({$attribute})";
							$where["{$column} = ?"] = strtolower($filtering[$attribute]);
						}
					}
					$iteration++;
				}
				if(isset($filtering["{$class}.ClassName"])) {
					$where["LOWER({$class}.ClassName) = ?"] = strtolower($filtering["{$class}.ClassName"]);
				}

				// Grab all data object visible attributes.

				$query = new SQLSelect(
					"{$class}.ClassName,{$select}{$class}.ID",
					$class,
					$where,
					$sorting,
					array(),
					array(),
					is_numeric($limit) ? $limit : array()
				);

				// Determine the tables with visible attributes to join.

				foreach($from as $join) {
					if(strpos($select, " {$join}.") !== false) {
						$query->addLeftJoin($join, "{$class}.ID = {$join}.ID");
					}
				}
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
	 *	@parameter <{FILTERS}> array
	 *	@parameter <{USE_ATTRIBUTE_VISIBILITY}> boolean
	 *	@parameter <{SET_CONTENT_HEADER}> boolean
	 *	@parameter <{WRAP_JAVASCRIPT_CALLBACK}> boolean
	 *	@return JSON
	 */

	public function retrieveJSON($objects, $filters = null, $attributeVisibility = false, $contentHeader = false, $callback = false) {

		Versioned::reading_stage('Live');

		// Convert the corresponding array of data objects to JSON.

		$temporary = array();
		foreach($objects as $object) {
			$classExists = isset($object['ClassName']);
			$class = $classExists ? $object['ClassName'] : 'DataObject';
			if($classExists && isset($object['ID'])) {

				// Compose the appropriate output for all relationships.

				$temporary[] = array(
					$class => $this->recursiveRelationships($object, $attributeVisibility)
				);
			}
			else {

				// This is custom JSON, therefore pass it through directly.

				unset($object['ClassName']);
				$temporary[] = array(
					$class => $object
				);
			}
		}

		// Display the filters, even if they weren't applied to visible attributes.

		$output = array();
		if($filters) {
			$output['Filters'] = $filters;
		}
		$output['Count'] = count($temporary);
		$output['DataObjects'] = $temporary;

		// JSON_PRETTY_PRINT.

		$JSON = json_encode(array(
			'APIwesome' => $output
		), $this->prettyJSON ? 128 : 0);

		// Apply a defined javascript callback function.

		$header = false;
		$class = is_subclass_of($objects[0]['ClassName'], 'SiteTree') ? 'SiteTree' : (is_subclass_of($objects[0]['ClassName'], 'File') ? 'File' : $objects[0]['ClassName']);
		if($callback && ($configuration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', array(
			'IsFor = ?' => $class
		)))) {
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
					if($relationship && ($relationObject = DataObject::get_by_id($object['ClassName'], $object['ID'])) && $relationObject->hasMethod($relationship) && ($value != 0)) {

						// Grab the relationship.

						$relationObject = $relationObject->$relationship();

						// Make sure recursive relationships are enabled.

						if(!$this->recursiveRelationships) {
							$output[$relationship] = array(
								$relationObject->ClassName => array(
									'ID' => (string)$relationObject->ID
								)
							);
							continue;
						}
						$temporaryMap = $relationObject->toMap();
						if($attributeVisibility) {

							// Grab the attribute visibility.

							$class = is_subclass_of($relationObject->ClassName, 'SiteTree') ? 'SiteTree' : (is_subclass_of($relationObject->ClassName, 'File') ? 'File' : $relationObject->ClassName);
							$relationConfiguration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', array(
								'IsFor = ?' => $class
							));
							$relationVisibility = ($relationConfiguration && $relationConfiguration->APIwesomeVisibility) ? explode(',', $relationConfiguration->APIwesomeVisibility) : null;
							$columns = array();
							foreach(ClassInfo::subclassesFor($class) as $subclass) {

								// Prepend the table names.

								$subclassColumns = array();
								foreach(DataObject::database_fields($subclass) as $column => $type) {
									$subclassColumns["{$subclass}.{$column}"] = $type;
								}
								$columns = array_merge($columns, $subclassColumns);
							}
							array_shift($columns);

							// Make sure this relationship has visibility customisation.

							if(is_null($relationVisibility) || (count($relationVisibility) !== count($columns)) || !in_array('1', $relationVisibility)) {
								$output[$relationship] = array(
									$relationObject->ClassName => array(
										'ID' => (string)$relationObject->ID
									)
								);
								continue;
							}

							// Grab all data object visible attributes.

							$select = array(
								'ClassName' => $relationObject->ClassName,
								'ID' => $relationObject->ID
							);
							$iteration = 0;
							foreach($columns as $relationshipAttribute => $relationshipType) {
								if(isset($relationVisibility[$iteration]) && $relationVisibility[$iteration]) {
									$split = explode('.', $relationshipAttribute);
									$relationshipAttribute = ((count($split) === 2) ? $split[1] : $relationshipAttribute);
									if(isset($temporaryMap[$relationshipAttribute]) && $temporaryMap[$relationshipAttribute]) {

										// Retrieve the relationship value, and compose any asset file paths.

										$relationshipValue = $temporaryMap[$relationshipAttribute];
										$select[$relationshipAttribute] = (((strpos(strtolower($relationshipAttribute), 'file') !== false) || (strpos(strtolower($relationshipAttribute), 'image') !== false)) && (strpos($relationshipValue, 'assets/') !== false)) ? Director::absoluteURL($relationshipValue) : (is_integer($relationshipValue) ? (string)$relationshipValue : $relationshipValue);
									}
								}
								$iteration++;
							}
						}
						else {
							$select = $temporaryMap;
						}

						// Check the corresponding relationship.

						$output[$relationship] = array(
							$relationObject->ClassName => $this->recursiveRelationships($select, $attributeVisibility, $cache)
						);
					}
					else {

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
	 *	@parameter <{FILTERS}> array
	 *	@parameter <{USE_ATTRIBUTE_VISIBILITY}> boolean
	 *	@parameter <{SET_CONTENT_HEADER}> boolean
	 *	@return XML
	 */

	public function retrieveXML($objects, $filters = null, $attributeVisibility = false, $contentHeader = false) {

		Versioned::reading_stage('Live');

		// Display the filters, even if they weren't applied to visible attributes.

		$XML = new SimpleXMLElement('<APIwesome/>');
		if($filters) {
			$filtersXML = $XML->addChild('Filters');
			foreach($filters as $attribute => $value) {
				$filtersXML->addChild($attribute, $value);
			}
		}
		$XML->addChild('Count', count($objects));

		// Convert the corresponding array of data objects to XML.

		$objectsXML = $XML->addChild('DataObjects');
		foreach($objects as $object) {
			$classExists = isset($object['ClassName']);
			$objectXML = $objectsXML->addChild($classExists ? $object['ClassName'] : 'DataObject');
			if($classExists && isset($object['ID'])) {

				// Compose the appropriate output for all relationships.

				$this->recursiveXML($objectXML, $this->recursiveRelationships($object, $attributeVisibility));
			}
			else {

				// This is custom XML, therefore pass it through directly.

				unset($object['ClassName']);
				$this->recursiveXML($objectXML, $object);
			}
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
		$objects = isset($temporary['APIwesome']['DataObjects']) ? $temporary['APIwesome']['DataObjects'] : null;
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

		$temporary = (strpos($XML, '<APIwesome>') && strrpos($XML, '</APIwesome>')) ? $this->recursiveXMLArray(new SimpleXMLElement($XML)) : null;
		if(is_array($temporary) && isset($temporary['DataObjects'])) {

			// Compose a format similar to that of the JSON.

			$objects = array();
			foreach($temporary['DataObjects'] as $class => $value) {
				foreach($value as $object) {
					$objects[] = array(
						$class => $object
					);
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
