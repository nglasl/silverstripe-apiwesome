<?php

/**
 *	APIwesome specific configuration settings.
 *	Don't change these!
 */

Object::add_extension('APIwesomeAdmin', 'APIwesomeAdminButtonExtension');
Object::add_extension('DataObject', 'DataObjectPreviewExtension');

/**
 *
 *	EXAMPLE: Custom data object JSON/XML exclusions.
 *	@param array(string)
 *
 *	DataObjectConfiguration::set_custom_exclusions(array(
 *		'MyFirstDataObjectName',
 *		'MySecondDataObjectName'
 *	));
 *
 */
