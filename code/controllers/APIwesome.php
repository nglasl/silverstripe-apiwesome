<?php

/**
 *	Passes the current request over to the APIwesomeService.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesome extends Controller {

	public $service;

	private static $dependencies = array(
		'service' => '%$APIwesomeService',
	);

	private static $allowed_actions = array(
		'regenerate',
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
	 *	Attempt to regenerate the current security token.
	 */

	public function regenerate() {

		// Restrict this functionality to administrators.

		if(Permission::checkMember(Member::currentUser(), 'ADMIN')) {

			// Attempt to create a random hash.

			$regeneration = $this->service->generateHash();
			if($regeneration) {

				// Instantiate the new security token.

				$token = APIwesomeToken::create();
				$token->Hash = $regeneration['hash'];
				$token->write();

				// Temporarily use the session to display the new security token key.

				Session::set('APIwesomeToken', "{$regeneration['key']}:{$regeneration['salt']}");
			}
			else {

				// Log the failed security token regeneration.

				SS_Log::log('APIwesome security token regeneration failed.', SS_Log::ERR);
				Session::set('APIwesomeToken', -1);
			}
			return $this->redirect('/admin/json-xml/');
		}
		else {
			return $this->httpError(404);
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
	 *	EXAMPLE FILTERS:	<{WEBSITE}>/apiwesome/retrieve/<data-object-name>/xml?limit=5&filter=Attribute,value&sort=Attribute,ORDER
	 *
	 */

	public function retrieve() {

		$parameters = $this->getRequest()->allParams();

		// Pass the current request parameters over to the APIwesomeService where valid.

		if($parameters['ID'] && $parameters['OtherID'] && ($validation = $this->validate($parameters['OtherID']))) {
			if(is_string($validation)) {
				return $validation;
			}

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

	/**
	 *	Confirm that the current request user token exists.
	 */

	private function validate($output) {

		// Compare the current security token hash against the user token.

		$currentToken = APIwesomeToken::get()->sort('Created', 'DESC')->first();
		$userToken = explode(':', $this->getRequest()->getVar('token'));
		if($currentToken && (count($userToken) === 2) && ($generation = $this->service->generateHash($userToken[0], $userToken[1]))) {
			$hash = $generation['hash'];
			if($currentToken->Hash === $hash) {
				return true;
			}

			// Determine whether the user token has been invalidated.

			else {
				$tokens = APIwesomeToken::get()->sort('Created', 'DESC');
				foreach($tokens as $token) {
					if($token->Hash === $hash) {

						// Return the appropriate JSON/XML output indicating the token expiry.

						$output = strtoupper($output);
						if($output === 'JSON') {
							$this->getResponse()->addHeader('Content-Type', 'application/json');
							$JSON = Convert::array2json(array('Expired' => true));
							return $JSON;
						}
						else if($output === 'XML') {
							$this->getResponse()->addHeader('Content-Type', 'application/xml');
							$XML = new SimpleXMLElement('<Expired>true</Expired>');
							return $XML->asXML();
						}
					}
				}
			}
		}

		// Invalid.

		return false;
	}

}
