<?php

/**
 *	APIwesome CMS interface for managing JSON/XML output configuration of an individual data object type.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeAdmin extends ModelAdmin {

	public static $managed_models = 'DataObjectOutputConfiguration';

	public static $menu_title = 'JSON/XML Configuration';

	public static $url_segment = 'json-xml';

}
