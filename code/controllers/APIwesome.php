<?php

/**
 *	Passes the current request over to the APIwesomeService.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesome extends Controller {

	public $service;

	public static $dependencies = array(
		'service' => '%$APIwesomeService',
	);

	public static $allowed_actions = array(
		'retrieve'
	);

	/**
	 *	Reject a direct APIwesome request.
	 */

	public function index() {

		return $this->httpError(404);
	}

	/**
	 *	Display an error page on invalid request.
	 *
	 *	@parameter <{ERROR_CODE}> integer
	 *	@parameter <{ERROR_MESSAGE}> string
	 */

	public function httpError($code, $message = null) {

		// Display the error page for the given status code.

		if($this->getRequest()->isMedia() || !($response = ErrorPage::response_for($code))) {
			return parent::httpError($code, $message);
		}
		else {
			throw new SS_HTTPResponse_Exception($response);
		}
	}

	/**
	 *
	 *	Retrieve the appropriate JSON/XML output of a specified data object type, with optional filters parsed from the GET request.
	 *
	 *	@URLparameter <{DATA_OBJECT_NAME}> string
	 *	@URLparameter <{OUTPUT_TYPE}> string
	 *	@URLfilter <{LIMIT}> integer
	 *	@URLfilter <{FILTER}> string
	 *	@URLfilter <{SORT}> string
	 *	@return JSON/XML
	 *
	 *	EXAMPLE JSON:		<{WEBSITE}>/apiwesome/retrieve/<data-object-name>/json
	 *	EXAMPLE XML:		<{WEBSITE}>/apiwesome/retrieve/<data-object-name>/xml
	 *	EXAMPLE FILTERS:	<{WEBSITE}>/apiwesome/retrieve/<data-object-name>/xml?limit=5&filter=column,value&sort=column,order
	 *
	 */

	public function retrieve() {

		$parameters = $this->getRequest()->allParams();

		// Pass the current request parameters over to the APIwesomeService if valid.

		if($parameters['ID'] && $parameters['OtherID']) {

			// Convert the data object name input to the class name.

			$name = explode('-', $parameters['ID']);
			$class = '';
			foreach($name as $partial) {
				$class .= ucfirst(strtolower($partial));
			}
			return $this->service->retrieve($class, $parameters['OtherID'], $this->getRequest()->getVar('limit'), explode(',', $this->getRequest()->getVar('filter')), explode(',', $this->getRequest()->getVar('sort')));
		}
		else {
			return $this->httpError(404);
		}
	}

}
