<?php

/**
 *	The CMS data object configuration so an individual JSON/XML data object output may be customised.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class DataObjectOutputConfiguration extends DataObject {

	public static $db = array(
		'IsFor' => 'VARCHAR(255)',
		'CallbackFunction' => 'VARCHAR(255)'
	);

	// The list of default data objects that we wish to ignore, including this configuration class.

	private static $exclusions = array(
		'DataObject',
		'Email_BounceRecord',
		'File',
		'Folder',
		'Image',
		'Image_Cached',
		'Group',
		'LoginAttempt',
		'Member',
		'MemberPassword',
		'Permission',
		'PermissionRole',
		'PermissionRoleCode',
		'SiteConfig',
		'SiteTree',
		'Page',
		'ErrorPage',
		'RedirectorPage',
		'VirtualPage',
		'LeftAndMainTest_Object',
		'ModelAdminTest_Contact',
		'ModelAdminTest_Player',
		'DataObjectOutputConfiguration'
	);

	private $objects;
	
	// The list of custom data objects that we wish to ignore.

	private static $custom_exclusions = array(
	);

	/**
	 *	Set the custom data object JSON/XML exclusions.
	 *
	 *	@param array(string)
	 */

	public static function set_custom_exclusions($custom_exclusions) {

		self::$custom_exclusions = $custom_exclusions;
	}

	/**
	 *	Apply all required object extensions, which is executed on project build.
	 *	This will take into account any exclusions that have been defined.
	 */

	public static function apply_required_extensions() {

		// Retrieve the list of objects that extend the base data object, along with any exclusions that have been defined.

		$objects = ClassInfo::subclassesFor('DataObject');
		$exclusions = array_unique(array_merge(self::$exclusions, self::$custom_exclusions));
		foreach($objects as $object) {
			if(is_subclass_of($object, 'DataObject') && !in_array($object, $exclusions)) {

				// Apply the required APIwesome extensions to each of these objects.

				Object::add_extension($object, 'DataObjectOutputExtension');
			}
		}

		// Apply any remaining required APIwesome extensions.

		Object::add_extension('APIwesomeAdmin', 'APIwesomeAdminExtension');
	}

	/**
	 *	The process to automatically construct configurations for existing data objects, which is executed on project build.
	 *	This will take into account any exclusions that have been defined.
	 */

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// Retrieve the list of data objects that have been completely removed, most likely from previous modules.

		$database = $GLOBALS['databaseConfig']['database'];
		$tables = DB::query('SHOW TABLES FROM ' . Convert::raw2sql($database));
		foreach($tables as $table) {
			$table = $table['Tables_in_' . $database];

			// If a configuration is found for a data object class that no longer exists.

			if(!class_exists($table)) {
				$existing = DataObjectOutputConfiguration::get()->filter(array('IsFor' => $table));

				// Remove these existing configurations.

				if($existing && $existing instanceof DataList && $existing->first()) {
					$existing->first()->delete();
					DB::alteration_message($table . ' JSON/XML Configuration', 'obsolete');
				}
			}
		}

		// Retrieve the list of valid data objects, along with any exclusions that have been defined.

		$objects = ClassInfo::subclassesFor('DataObject');
		$exclusions = array_unique(array_merge(self::$exclusions, self::$custom_exclusions));
		foreach($objects as $object) {
			$existing = DataObjectOutputConfiguration::get()->filter(array('IsFor' => $object));

			// If a configuration is found for new custom exclusions.

			if(is_subclass_of($object, 'DataObject') && in_array($object, $exclusions)) {

				// Remove these existing configurations.

				if($existing && $existing instanceof DataList && $existing->first()) {
					$existing->first()->delete();
					DB::alteration_message($object . ' JSON/XML Configuration', 'deleted');
				}
			}

			// If we find a new data object that isn't excluded.

			else if(is_subclass_of($object, 'DataObject') && !in_array($object, $exclusions)) {

				// Create a new configuration for this data object.

				if(is_null($existing) || ($existing instanceof DataList && is_null($existing->first()))) {
					$configuration = DataObjectOutputConfiguration::create();

					// Assign the data object against this configuration, to make sure only one instance will exist per data object.

					$configuration->IsFor = $object;
					$configuration->write();
					DB::alteration_message($object . ' JSON/XML Configuration', 'created');
				}
			}
		}
	}

	/**
	 *	Update the CMS interface to allow customisation of JSON/XML output for individual data objects.
	 */

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// The data object that is assigned to this configuration must not be changed.

		$fields->removeByName('IsFor');

		// add a default value for the visibility column
		// grab the visibility column for this data object
		// if null, make a checkbox for each column and set it to not visible
		// otherwise, use the number of values to set the number of columns listed, so {0,0,0,1,0} would make the fourth column visible, and the rest not visible
		// on save, write a certain format back to the column such as {0,0,0,1,0} matching the number of columns minus ID and ClassName

		return $fields;
	}

}
