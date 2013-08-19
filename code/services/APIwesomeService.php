<?php

/**
 *	The heart of the module's functionality, which handles the request URL and outputs the appropriate JSON/XML of data objects.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeService {

	/**
	 *
	 *	Retrieve the appropriate JSON/XML of the specified data object either by function call or request URL, including any user customisation.
	 *
	 *	@param string
	 *	@param string
	 *
	 *	EXAMPLE JSON:	{WEBSITE}/apiwesome/retrieve/my-first-data-object-name/json
	 *	EXAMPLE XML:	{WEBSITE}/apiwesome/retrieve/my-second-data-object-name/xml
	 *
	 */

	public function retrieve($objectName = null, $type = null) {

		// Make sure this JSON/XML request is valid.

		$parameters = Controller::curr()->getRequest()->allParams();
		if(!isset($parameters['ID']) && !isset($parameters['OtherID'])) {
			$parameters['ID'] = ($objectName && is_string($objectName)) ? $objectName : null;
			$parameters['OtherID'] = ($type && is_string($type)) ? $type : null;
		}

		// Make sure the request contains the two required parameters.

		if($parameters['ID'] && $parameters['OtherID']) {

			// Validate and return these data objects.

			$objects = $this->validate($parameters['ID']);
			$type = strtoupper($parameters['OtherID']);

			// Redirect this request URL towards the appropriate JSON/XML retrieval of data objects.

			if($objects && ($type === 'JSON')) {
				return $this->retrieveJSON($objects, true);
			}
			else if($objects && ($type === 'XML')) {
				return $this->retrieveXML($objects, true);
			}
		}

		// The request URL was not valid.

		return Controller::curr()->httpError(404);
	}

	/**
	 *	Compose the appropriate JSON for the corresponding array of data objects.
	 *	Make sure to convert your data list using toNestedArray.
	 *
	 *	@param array
	 *	@return JSON
	 */

	public function retrieveJSON($objects, $addHeader = false) {

		// Convert the input array to JSON.
		
		$JSON = array();
		foreach($objects as $temporary) {

			// Remove attributes that are not required.

			$object = array();
			foreach($temporary as $attribute => $value) {
				if(($attribute !== 'ClassName') && ($attribute !== 'APIwesomeVisibility')) {
					$object[$attribute] = $value;
				}
			}
			$JSON[] = array($temporary['ClassName'] => $object);
		}
		$JSON = Convert::array2json(array('DataObjectList' => $JSON));

		// Apply the callback function from configuration.

		$configuration = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($objects[0]['ClassName']) . "'");
		$JSON = $configuration->CallbackFunction ? str_replace(' ', '_', $configuration->CallbackFunction) . "($JSON);" : $JSON;

		// Set the response header, and return the JSON.

		if($addHeader) {
			$configuration->CallbackFunction ? Controller::curr()->getResponse()->addHeader('Content-Type', 'application/javascript') : Controller::curr()->getResponse()->addHeader('Content-Type', 'application/json');
		}
		return $JSON;
	}

	/**
	 *	Compose the appropriate XML for the corresponding array of data objects.
	 *	Make sure to convert your data list using toNestedArray.
	 *
	 *	@param array
	 *	@return XML
	 */

	public function retrieveXML($objects, $addHeader = false) {

		// Convert the input array to XML.

		$XML = new SimpleXMLElement('<DataObjectList/>');
		foreach($objects as $object) {

			// Add the data objects in the correct format, using the data object name as a parent element.

			$objectXML = $XML->addChild($object['ClassName']);
			foreach($object as $attribute => $value) {

				// Remove attributes that are not required.

				if(($attribute !== 'ClassName') && ($attribute !== 'APIwesomeVisibility')) {
					$objectXML->addChild($attribute, $value);
				}
			}
		}

		// Set the response header, and return the XML.

		if($addHeader) {
			Controller::curr()->getResponse()->addHeader('Content-Type', 'application/xml');
		}
		return $XML->asXML();
	}

	/**
	 *	Validate and return the corresponding data objects with an associated configuration, only including visible attributes.
	 *
	 *	@param string
	 *	@return DataList
	 */

	private function validate($class) {

		// Convert the data object name expected format to that required by the database query.

		$input = explode('-', $class);
		$class = '';
		foreach($input as $partial) {
			$class .= ucfirst(strtolower($partial));
		}

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

}
