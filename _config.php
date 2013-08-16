<?php

/**
 *	APIwesome specific configuration settings. Don't change these!
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

if(!defined('APIWESOME_PATH')) {
	define('APIWESOME_PATH', rtrim(basename(dirname(__FILE__))));
}
DataObjectOutputConfiguration::apply_required_extensions();

/**
 *
 *	EXAMPLE: JSON/XML custom data object exclusions.
 *
 *	@param array(string)
 *
 *	DataObjectOutputConfiguration::add_custom_exclusions(array(
 *		'MyFirstDataObjectName',
 *		'MySecondDataObjectName'
 *	));
 *
 */

/**
 *
 *	EXAMPLE: Replace JSON/XML custom data object exclusions with inclusions, changing all data object visibility to hidden except for those defined.
 *	NOTE: This will change the exclusions array definition for you, so you only need to update the data object names listed. This will require a project build.
 *
 *	@param boolean
 *
 *	DataObjectOutputConfiguration::exclusions_to_inclusions(true);
 *
 */
