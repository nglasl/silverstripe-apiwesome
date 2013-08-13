<?php

/**
 *	The heart of the module's functionality, which handles the request URL and outputs the appropriate JSON/XML of data objects.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesome extends Controller {

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
				return $this->displayJSON($objects);
			}
			else if($objects && ($type === 'XML')) {
				return $this->displayXML($objects);
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

	public function validate($class) {

		// Convert the data object name expected format to that required by the database query.

		$input = explode('-', $class);
		$class = '';
		foreach($input as $partial) {
			$class .= ucfirst(strtolower($partial));
		}

		// Make sure at least one data object and configuration exist, otherwise this request is considered invalid.

		if(in_array($class, ClassInfo::subclassesFor('DataObject'))) {
			$objects = DataObject::get($class);
			$configuration = DataObjectOutputConfiguration::get()->filter(array('IsFor' => $class));
			if(($objects && $configuration) && ($objects instanceof DataList && $configuration instanceof DataList) && ($objects->first() && $configuration->first())) {

				// Return our list of validated data objects.

				return $objects;
			}
		}

		// The data object name input was not valid.

		return null;
	}

	/**
	 *	Compose the appropriate JSON for the corresponding data objects.
	 *
	 *	@param DataList
	 *	@return JSON
	 */

	public function displayJSON($objects) {

		$json = Convert::array2json($objects->toNestedArray());
		$this->getResponse()->addHeader('Content-Type', 'application/javascript');
		return $json;
	}

	/**
	 *	Compose the appropriate XML for the corresponding data objects.
	 *
	 *	@param DataList
	 *	@return XML
	 */

	public function displayXML($objects) {

		$xml = Convert::raw2xml($objects->toNestedArray());
		$this->getResponse()->addHeader('Content-Type', 'application/xml');
		return $xml;
	}

}
