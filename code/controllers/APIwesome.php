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
	 *	@parameter integer
	 *	@parameter string
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
	 *	Retrieve the appropriate JSON/XML output of a specified data object type.
	 *
	 *	@URLparameter string
	 *	@URLparameter string
	 *	@return JSON/XML
	 *
	 *	EXAMPLE JSON:	<WEBSITE>/apiwesome/retrieve/my-first-data-object-name/json
	 *	EXAMPLE XML:	<WEBSITE>/apiwesome/retrieve/my-second-data-object-name/xml
	 *
	 */

	public function retrieve() {

		return $this->service->retrieve();
	}

}
