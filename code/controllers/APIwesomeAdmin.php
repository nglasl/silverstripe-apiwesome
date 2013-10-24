<?php

/**
 *	APIwesome CMS interface for managing JSON/XML output configuration of an individual data object type.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeAdmin extends ModelAdmin {

	private static $managed_models = 'DataObjectOutputConfiguration';

	private static $menu_title = 'JSON/XML Configuration';

	private static $url_segment = 'json-xml';

}
