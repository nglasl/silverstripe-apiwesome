<?php

/**
 *	APIwesome CMS JSON/XML output configuration of an individual data object type.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class DataObjectOutputConfiguration extends DataObject {

	public static $db = array(
		'IsFor' => 'Varchar(255)',
		'CallbackFunction' => 'Varchar(255)'
	);

	public static $default_sort = 'IsFor';

	public static $searchable_fields = array(
		'IsFor',
		'CallbackFunction'
	);

	public static $summary_fields = array(
		'printIsFor'
	);

	public static $field_labels = array(
		'printIsFor' => 'Is For'
	);

	/**
	 *	The default data objects to exclude.
	 */

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
	
	/**
	 *	The custom data objects to exclude, defined under project configuration.
	 */

	private static $custom_exclusions = array(
	);
	
	/**
	 *	The custom data objects to include, defined under project configuration.
	 */

	private static $custom_inclusions = array(
	);

	/**
	 *	Apply all APIwesome required extensions.
	 */

	public static function apply_required_extensions() {

		// Grab the list of all data object types, along with any inclusions/exclusions defined.

		$objects = ClassInfo::subclassesFor('DataObject');
		$inclusions = self::$custom_inclusions;
		$exclusions = array_unique(array_merge(self::$exclusions, self::$custom_exclusions));

		// Apply extensions to each valid data object found.

		foreach($objects as $object) {

			// Apply the extension to each data object not excluded, unless inclusions have been defined.

			if(is_subclass_of($object, 'DataObject') && (((count($inclusions) > 0) && in_array($object, $inclusions)) || ((count($inclusions) === 0) && !in_array($object, $exclusions)))) {
				Object::add_extension($object, 'DataObjectOutputExtension');
			}
		}

		// Apply the remaining required extensions.

		Object::add_extension('APIwesomeAdmin', 'APIwesomeAdminExtension');
		Object::add_extension('ModelAdmin', 'ModelAdminPreviewExtension');
	}

	/**
	 *	Set JSON/XML data object exclusions/inclusions.
	 *	NOTE: All data objects are included by default (excluding core), unless inclusions have explicitly been defined.
	 *
	 *	@parameter <{FILTER_TYPE}> string
	 *	@parameter <{DATA_OBJECT_NAMES}> array(string)
	 */

	public static function customise_data_objects($type, $objects) {

		// Merge the exclusions/inclusions in case of multiple definitions.

		if(is_array($objects) && (strtolower($type) === 'exclude')) {
			self::$custom_exclusions = array_unique(array_merge(self::$custom_exclusions, $objects));
		}
		else if(is_array($objects) && (strtolower($type) === 'include')) {
			self::$custom_inclusions = array_unique(array_merge(self::$custom_inclusions, $objects));
		}
	}

	/**
	 *	The process to automatically construct data object output configurations, executed on project build.
	 */

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// Using MySQL, grab the list of data objects that have been completely removed.

		$database = $GLOBALS['databaseConfig']['database'];
		if(strtoupper($database) === 'MYSQLDATABASE') {
			$tables = DB::query('SHOW TABLES FROM ' . Convert::raw2sql($database));
			foreach($tables as $table) {
				$table = $table['Tables_in_' . $database];

				// Delete existing output configurations for these data objects.

				if(!class_exists($table)) {
					$existing = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($table) . "'");
					$this->deleteConfiguration($table, $existing);
				}
			}
		}

		// Grab the list of all data object types, along with any inclusions/exclusions defined.

		$objects = ClassInfo::subclassesFor('DataObject');
		$inclusions = self::$custom_inclusions;
		$exclusions = array_unique(array_merge(self::$exclusions, self::$custom_exclusions));

		// Check existing output configurations for these data objects.

		foreach($objects as $object) {
			$existing = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', "IsFor = '" . Convert::raw2sql($object) . "'");

			// Delete existing output configurations for data objects excluded.

			if(is_subclass_of($object, 'DataObject') && (((count($inclusions) > 0) && !in_array($object, $inclusions)) || ((count($inclusions) === 0) && in_array($object, $exclusions)))) {
				$this->deleteConfiguration($object, $existing);
			}

			// Add an output configuration for new data objects.

			else if(!$existing && is_subclass_of($object, 'DataObject') && (((count($inclusions) > 0) && in_array($object, $inclusions)) || ((count($inclusions) === 0) && !in_array($object, $exclusions)))) {
				$this->addConfiguration($object);
			}
		}
	}

	/**
	 *	Print the data object name associated with this configuration.
	 *
	 *	@return string
	 */

	public function printIsFor() {

		// Add spaces between words.

		return ltrim(preg_replace('/[A-Z]+[^A-Z]/', ' $0', $this->IsFor));
	}

	/**
	 *	Display CMS JSON/XML output visibility configuration.
	 */

	public function getCMSFields() {

		$fields = parent::getCMSFields();

		// Hide the data object name associated with this configuration.

		$fields->removeByName('IsFor');

		// Grab a single data object.

		Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');
		if($object = DataObject::get_one($this->IsFor, '', true, 'APIwesomeVisibility DESC')) {

			// Grab the appropriate attributes for this data object.

			$columns = DataObject::database_fields($this->IsFor);
			array_shift($columns);
			$visibility = $object->APIwesomeVisibility ? explode(',', $object->APIwesomeVisibility) : null;

			// Display the check box fields for JSON/XML output visibility.

			$configuration = FieldGroup::create(
				'Visibility'
			)->addExtraClass('visibility');
			$iteration = 0;
			foreach($columns as $name => $type) {

				// Ignore the visibility attribute.

				if($name !== 'APIwesomeVisibility') {

					// Print the attribute name, including any relationships.

					$printName = ltrim(preg_replace('/[A-Z]+[^A-Z]/', ' $0', $name));
					$printName = (substr($printName, strlen($printName) - 2) === 'ID') ? substr($printName, 0, -2) : $printName;

					// Set an already existing attribute visibility.

					$configuration->push(CheckboxField::create(
						"{$name}Visibility",
						"Display <strong>{$printName}</strong>?",
						(isset($visibility[$iteration])) ? $visibility[$iteration] : 0
					)->addExtraClass('visibility'));
					$iteration++;
				}
			}
			$fields->addFieldToTab('Root.Main', $configuration);
		}
		else {

			// Display a notification that a data object should first be created.

			$fields->removeByName('CallbackFunction');
			$name = $this->printIsFor();
			$fields->addFieldToTab('Root.Main', LiteralField::create(
				'ConfigurationNotification',
				"<p class='apiwesome notification'><strong>No {$name}s Found</strong></p>"
			));
		}
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}

	/**
	 *	Save the JSON/XML output visibility customisation for each associated data object.
	 */

	public function onAfterWrite() {

		parent::onAfterWrite();

		// Append the visibility customisation to a string.

		$visibility = '';
		foreach($this->record as $name => $value) {
			if(strrpos($name, 'Visibility')) {
				$value = $value ? $value : 0;
				$visibility .= "{$value},";
			}
		}
		$visibility = rtrim($visibility, ',');

		// Write this visibility customisation string.

		$objects = DataObject::get($this->IsFor);
		if($objects && $objects instanceof DataList) {
			foreach($objects as $object) {
				$object->APIwesomeVisibility = $visibility;
				$object->write();
			}
		}
	}

	/**
	 *	Add an output configuration for a new data object.
	 */

	private function addConfiguration($object) {

		// Create a new output configuration.

		$configuration = DataObjectOutputConfiguration::create();

		// Assign the data object against this configuration.

		$configuration->IsFor = $object;
		$configuration->write();
		DB::alteration_message($object . ' JSON/XML Configuration', 'created');
	}

	/**
	 *	Delete an existing output configuration for a data object now excluded.
	 */

	private function deleteConfiguration($object, $existing) {
		if($existing) {
			$existing->delete();
			DB::alteration_message($object . ' JSON/XML Configuration', 'deleted');
		}
	}

}
