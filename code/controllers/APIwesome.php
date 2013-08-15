<?php

/**
 *	The heart of the module's functionality, which handles the request URL and outputs the appropriate JSON/XML of data objects.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesome extends Controller {

	public static $allowed_actions = array(
		'retrieve'
	);

	/**
	 *
	 *	Retrieve the appropriate JSON/XML of the specified data object by request URL, including any user customisation.
	 *
	 *	@param string
	 *	@param string
	 *
	 *	EXAMPLE JSON:	{WEBSITE}/apiwesome/retrieve/my-first-data-object-name/json
	 *	EXAMPLE XML:	{WEBSITE}/apiwesome/retrieve/my-second-data-object-name/xml
	 *
	 */

	public function retrieve() {

		// Make sure the request URL contains the two required parameters.

		$parameters = $this->getRequest()->allParams();
		if($parameters['ID'] && $parameters['OtherID']) {

			// Validate and return these data objects.

			$objects = $this->validate($parameters['ID']);
			$type = strtoupper($parameters['OtherID']);

			// Redirect this request URL towards the appropriate JSON/XML retrieval of data objects.

			if($objects && ($type === 'JSON')) {
				return $this->retrieveJSON($objects);
			}
			else if($objects && ($type === 'XML')) {
				return $this->retrieveXML($objects);
			}
		}

		// The request URL was not valid.

		$this->httpError('404');
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
			$objects = DataObject::get($class)->sort('APIwesomeVisibility DESC');
			$configuration = DataObjectOutputConfiguration::get()->filter(array('IsFor' => $class));
			if(($objects && $configuration) && ($objects instanceof DataList && $configuration instanceof DataList) && ($objects->first() && $configuration->first())) {
				$object = $objects->first();

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

					$query = new SQLQuery("ClassName, ID, $select", array($class));
					$query = $query->execute();
					$objects = array();
					foreach($query as $object) {
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
	 *	Compose the appropriate JSON for the corresponding data objects.
	 *
	 *	@param array
	 *	@return JSON
	 */

	private function retrieveJSON($objects) {

		// Convert the input array to JSON.

		$JSON = array();
		foreach($objects as $object) {
			$JSON[] = array($object['ClassName'] => array_slice($object, 1));
		}
		$JSON = Convert::array2json(array('DataObjectList' => $JSON));

		// Apply the callback function from configuration.

		$configuration = DataObjectOutputConfiguration::get()->filter(array('IsFor' => $objects[0]['ClassName']))->first();
		$JSON = $configuration->CallbackFunction ? str_replace(' ', '_', $configuration->CallbackFunction) . "($JSON);" : $JSON;

		// Set the response header, and return the JSON.

		$configuration->CallbackFunction ? $this->getResponse()->addHeader('Content-Type', 'application/javascript') : $this->getResponse()->addHeader('Content-Type', 'application/json');
		return $JSON;
	}

	/**
	 *	Compose the appropriate XML for the corresponding data objects.
	 *
	 *	@param array
	 *	@return XML
	 */

	private function retrieveXML($objects) {

		// Convert the input array to XML.

		$XML = new SimpleXMLElement('<DataObjectList/>');
		foreach($objects as $key => $object) {

			// Add the data objects in the correct format.

			$objectXML = null;
			foreach($object as $attribute => $value) {

				// Use the data object name as a root element.

				if($attribute === 'ClassName') {
					$objectXML = $XML->addChild($value);
					continue;
				}
				$objectXML->addChild($attribute, $value);
			}
		}

		// Set the response header, and return the XML.

		$this->getResponse()->addHeader('Content-Type', 'application/xml');
		return $XML->asXML();
	}

}
