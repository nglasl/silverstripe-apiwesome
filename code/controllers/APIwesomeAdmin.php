<?php

/**
 *	The CMS model admin interface used to change JSON/XML feed configurations for individual data objects.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeAdmin extends ModelAdmin {

	public static $url_segment = 'json-xml-configuration';
	public static $menu_title = 'JSON/XML Configuration';
	public static $managed_models = 'DataObjectConfiguration';

}
