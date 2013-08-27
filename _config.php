<?php

/**
 *	APIwesome specific configuration settings.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

if(!defined('APIWESOME_PATH')) {
	define('APIWESOME_PATH', rtrim(basename(dirname(__FILE__))));
}
DataObjectOutputConfiguration::apply_required_extensions();

/**
 *
 *	EXAMPLE: JSON/XML data object exclusions/inclusions.
 *	NOTE: All data objects are included by default, unless inclusions have been defined.
 *
 *	@param string
 *	@param array(string)
 *
 *	DataObjectOutputConfiguration::customise_data_objects('exclude', array(
 *		'MyFirstDataObjectName',
 *		'MySecondDataObjectName',
 *		'MyThirdDataObjectName'
 *	));
 *
 *	DataObjectOutputConfiguration::customise_data_objects('include', array(
 *		'MyFirstDataObjectName',
 *		'MySecondDataObjectName',
 *		'MyThirdDataObjectName'
 *	));
 *
 */
