<?php

/**
 *	The CMS data object configuration so an individual JSON/XML data object feed may be customised.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class DataObjectConfiguration extends DataObject {

	public static $db = array(
		'CallbackFunction' => 'Text',
		'IsFor' => 'Text'
	);

	// The list of default data objects that we wish to ignore, including this configuration class.

	private $exclusions = array(
		'DataObjectConfiguration',
		'Email_BounceRecord',
		'ErrorPage',
		'File',
		'Group',
		'LoginAttempt',
		'Member',
		'MemberPassword',
		'Permission',
		'PermissionRole',
		'PermissionRoleCode',
		'RedirectorPage',
		'SiteConfig',
		'SiteTree',
		'VirtualPage'
	);
	
	// The list of custom data objects that we wish to ignore.

	private static $custom_exclusions = array(
	);

	/**
	 *	Set the custom data object JSON/XML exclusions.
	 *	@param array(string)
	 */

	public static function set_custom_exclusions($custom_exclusions) {

		self::$custom_exclusions = $custom_exclusions;
	}

	/**
	 *	The process to automatically create configurations for existing data objects, which is executed on project build.
	 *	This will take into account any exclusions that have been defined.
	 */

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// Using the project database name, query the database for a list of existing tables.

		$database = $GLOBALS['databaseConfig']['database'];
		$tables = DB::query('SHOW TABLES FROM ' . $database);

		// Filter out tables which don't extend a data object, including any exclusions that have been defined.

		$exclusions = array_merge($this->exclusions, self::$custom_exclusions);
		foreach($tables as $table) {
			$table = $table['Tables_in_' . $database];
			if(is_subclass_of($table, 'DataObject') && !in_array($table, $exclusions)) {

				// Using this data object, create a new configuration if one does not already exist.

				$existing = DataObjectConfiguration::get()->filter(array('IsFor' => $table));
				if(is_null($existing) || ($existing instanceof DataList && is_null($existing->first()))) {
					$configuration = DataObjectConfiguration::create();

					// Assign the data object against this configuration, to make sure only one instance will exist per data object.

					$configuration->IsFor = $table;
					$configuration->write();
					DB::alteration_message($table . ' JSON/XML Configuration', 'created');
				}
			}
		}
	}

	/**
	 *	The validation to make sure a configuration is only set once per data object.
	 */

	// We need to check the "IsFor" to make sure it is a data object, but also the dataconfigs so we don't have a clash. Use a dropdown for this CMS selection process using the below stuff rather than confirmation?

	public function validate() {

		// Instantiate a new validation result and search for a matching data object configuration.

		$result = new ValidationResult();
		$existingConfiguration = DataObjectConfiguration::get()->filter(array('IsFor' => $this->IsFor));

		// Determine whether to allow the new configuration, setting the validation result appropriately.

		if(!$existingConfiguration) {
			$result->valid();
		}

		return $result;

	}

}
