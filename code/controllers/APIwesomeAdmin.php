<?php

/**
 *	The CMS interface used to change JSON/XML output configurations for individual data objects.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class APIwesomeAdmin extends ModelAdmin {

	public static $url_segment = 'json-xml-configuration';

	public static $menu_title = 'JSON/XML Configuration';

	public static $managed_models = 'DataObjectOutputConfiguration';

}
