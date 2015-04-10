<?php

/**
 *	The apiwesome specific configuration settings.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

if(!defined('APIWESOME_PATH')) {
	define('APIWESOME_PATH', rtrim(basename(dirname(__FILE__))));
}

/**
 *
 *	EXAMPLE: JSON/XML data object exclusions/inclusions.
 *	NOTE: ALL data objects are included by default (excluding most core), unless disabled or inclusions have explicitly been defined.
 *
 *	@parameter <{FILTER_TYPE}> string
 *	@parameter <{DATA_OBJECT_NAMES}> array(string)
 *
 *	DataObjectOutputConfiguration::customise_data_objects('exclude', array(
 *		'<DataObjectName>',
 *		'<DataObjectName>',
 *		'<DataObjectName>'
 *	));
 *
 *	DataObjectOutputConfiguration::customise_data_objects('include', array(
 *		'<DataObjectName>',
 *		'<DataObjectName>',
 *		'<DataObjectName>'
 *	));
 *
 *	DataObjectOutputConfiguration::customise_data_objects('disabled');
 *
 */
