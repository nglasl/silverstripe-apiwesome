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
 *	DataObjectOutputConfiguration::set_custom_exclusions(array(
 *		'MyFirstDataObjectName',
 *		'MySecondDataObjectName'
 *	));
 *
 */

/**
 *
 *	EXAMPLE: JSON/XML custom data object inclusions.
 *	NOTE: This will disable the automatic JSON/XML configuration of all data objects by default.
 *
 *	@param array(string)
 *
 *	DataObjectOutputConfiguration::set_custom_inclusions(array(
 *		'MyFirstDataObjectName',
 *		'MySecondDataObjectName'
 *	));
 *
 */
