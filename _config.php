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
 *	EXAMPLE: JSON/XML custom data object exclusions/inclusions.
 *	NOTE: Inclusions will take precedence over exclusions, which will change all data object visibility to hidden except for those defined.
 *
 *	@param string
 *	@param array(string)
 *
 *	DataObjectOutputConfiguration::customise_data_objects('exclude', array(
 *		'MyFirstDataObjectName',
 *		'MySecondDataObjectName'
 *	));
 *
 *	DataObjectOutputConfiguration::customise_data_objects('include', array(
 *		'MyFirstDataObjectName',
 *		'MySecondDataObjectName'
 *	));
 *
 */
