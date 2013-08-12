<?php

/**
 *	The heart of the module's functionality, which handles the request URL and outputs the appropriate JSON/XML of data objects.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesome extends Controller {

	public function display() {

		// Redirect this request URL towards the appropriate JSON/XML retrieval of data objects.

		$parameters = $this->getRequest()->allParams();
		if($parameters['ID'] && $parameters['OtherID']) {
			$type = strtoupper($parameters['OtherID']);
			if($type == 'JSON') {
				$this->displayJSON();
				return;
			}
			else if($type == 'XML') {
				$this->displayXML();
				return;
			}
		}

		// This is the result of an incorrect request URL.

		return;
	}

	public function displayJSON() {

	}

	public function displayXML() {

	}

}
