<?php

/**
 *	Passes the current request over to the APIwesomeService.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesome extends Controller {

	public $service;

	private static $dependencies = array(
		'service' => '%$APIwesomeService'
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

		// Determine the error page for the given status code.

		$errorPages = ErrorPage::get()->filter('ErrorCode', $code);

		// Allow extension customisation.

		$this->extend('updateErrorPages', $errorPages);

		// Retrieve the error page response.

		if($errorPage = $errorPages->first()) {
			Requirements::clear();
			Requirements::clear_combined_files();
			$response = ModelAsController::controller_for($errorPage)->handleRequest(new SS_HTTPRequest('GET', ''), DataModel::inst());
			throw new SS_HTTPResponse_Exception($response, $code);
		}

		// Retrieve the cached error page response.

		else if(file_exists($cachedPage = ErrorPage::get_filepath_for_errorcode($code, class_exists('Translatable') ? Translatable::get_current_locale() : null))) {
			$response = new SS_HTTPResponse();
			$response->setStatusCode($code);
			$response->setBody(file_get_contents($cachedPage));
			throw new SS_HTTPResponse_Exception($response, $code);
		}
		else {
			return parent::httpError($code, $message);
		}
	}

	/**
	 *	Attempt to regenerate the current security token.
	 */

	public function regenerate() {

		// Restrict this functionality to administrators.

		$user = Member::currentUserID();
		if(Permission::checkMember($user, 'ADMIN')) {

			// Attempt to create a random hash.

			$regeneration = $this->service->generateHash();
			if($regeneration) {

				// Instantiate the new security token.

				$token = APIwesomeToken::create();
				$token->Hash = $regeneration['hash'];
				$token->AdministratorID = $user;
				$token->write();

				// Temporarily use the session to display the new security token key.

				Session::set('APIwesomeToken', "{$regeneration['key']}:{$regeneration['salt']}");
			}
			else {

				// Log the failed security token regeneration.

				SS_Log::log('APIwesome security token regeneration failed.', SS_Log::ERR);
				Session::set('APIwesomeToken', -1);
			}
			return $this->redirect('admin/json-xml/');
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
	 *	@URLfilter <{SORT}> string
	 *	@URLfilters <{FILTERS}> string
	 *	@return JSON/XML
	 *
	 *	EXAMPLE JSON:		<{WEBSITE}>/apiwesome/retrieve/<data-object-name>/json
	 *	EXAMPLE XML:		<{WEBSITE}>/apiwesome/retrieve/<data-object-name>/xml
	 *	EXAMPLE FILTERS:	<{WEBSITE}>/apiwesome/retrieve/<data-object-name>/xml?limit=5&sort=Attribute,ORDER&filter1=value&filter2=value
	 *
	 */

	public function retrieve() {

		$parameters = $this->getRequest()->allParams();

		// Pass the current request parameters over to the APIwesomeService where valid.

		if($parameters['ID'] && $parameters['OtherID'] && ($validation = $this->validate($parameters['OtherID']))) {
			if(is_string($validation)) {
				return $validation;
			}

			// Retrieve the specified data object type JSON/XML.

			$filters = $this->getRequest()->getVars();
			unset($filters['url'], $filters['token'], $filters['limit'], $filters['sort']);
			return $this->service->retrieve(str_replace('-', '', $parameters['ID']), $parameters['OtherID'], $this->getRequest()->getVar('limit'), explode(',', $this->getRequest()->getVar('sort')), $filters);
		}
		else {
			return $this->httpError(404);
		}
	}

	/**
	 *	Confirm that the current request user token exists.
	 */

	public function validate($output) {

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

							// JSON_PRETTY_PRINT.

							$JSON = json_encode(array(
								'APIwesome' => array(
									'Count' => 0,
									'DataObjects' => array(
										'Expired' => true
									)
								)
							), 128);
							return $JSON;
						}
						else if($output === 'XML') {
							$this->getResponse()->addHeader('Content-Type', 'application/xml');
							$XML = new SimpleXMLElement('<APIwesome/>');
							$XML->addChild('Count', 0);
							$objectsXML = $XML->addChild('DataObjects');
							$objectsXML->addChild('Expired', true);
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
