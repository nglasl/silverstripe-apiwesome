<?php

/**
 *	APIwesome CMS interface for managing JSON/XML output configuration of an individual data object type.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeAdmin extends ModelAdmin {

	private static $managed_models = 'DataObjectOutputConfiguration';

	private static $menu_title = 'JSON/XML';

	private static $menu_description = 'The <strong>JSON/XML</strong> feed will only be available to data objects with attribute visibility set through here. All data objects are included by default, unless exclusions or inclusions have explicitly been defined.';

	private static $url_segment = 'json-xml';

	/**
	 *	Correct the button styling and implement a confirmation message for security token regeneration.
	 */

	public function init() {

		parent::init();
		Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');
		Requirements::javascript(APIWESOME_PATH . '/javascript/apiwesome.js');
	}

}
