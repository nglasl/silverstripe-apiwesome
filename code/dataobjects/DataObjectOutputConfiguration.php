<?php

/**
 *	APIwesome CMS JSON/XML output configuration of an individual data object type.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class DataObjectOutputConfiguration extends DataObject {

	private static $db = array(
		'IsFor' => 'Varchar(255)',
		'APIwesomeVisibility' => 'Text',
		'CallbackFunction' => 'Varchar(255)'
	);

	private static $default_sort = 'IsFor';

	private static $searchable_fields = array(
		'IsFor'
	);

	private static $summary_fields = array(
		'getTitle'
	);

	private static $field_labels = array(
		'getTitle' => 'Is For'
	);

	/**
	 *	The default data objects to exclude.
	 */

	private static $exclusions = array(
		'APIwesomeToken',
		'DataObjectOutputConfiguration',
		'Group',
		'LoginAttempt',
		'Member',
		'MemberPassword',
		'Permission',
		'PermissionRole',
		'PermissionRoleCode',
		'SiteConfig'
	);

	/**
	 *	The custom data objects to exclude, defined under project configuration.
	 */

	private static $custom_exclusions = array();

	/**
	 *	The custom data objects to include, defined under project configuration.
	 */

	private static $custom_inclusions = array();

	/**
	 *	Flag used to prevent data object output, defined under project configuration.
	 */

	private static $disabled = false;

	/**
	 *	Set JSON/XML data object exclusions/inclusions.
	 *	NOTE: ALL data objects are included by default (excluding some core), unless disabled or inclusions have explicitly been defined.
	 *
	 *	@parameter <{FILTER_TYPE}> string
	 *	@parameter <{DATA_OBJECT_NAMES}> array(string)
	 */

	public static function customise_data_objects($type, $objects = null) {

		// Merge the exclusions/inclusions in case of multiple definitions.

		if(is_array($objects) && (strtolower($type) === 'exclude')) {
			self::$custom_exclusions = array_unique(array_merge(self::$custom_exclusions, $objects));
		}
		else if(is_array($objects) && (strtolower($type) === 'include')) {
			self::$custom_inclusions = array_unique(array_merge(self::$custom_inclusions, $objects));
		}
		else if(strtolower($type) === 'disabled') {
			self::$disabled = true;

			// The CMS interface will no longer be required.

			CMSMenu::remove_menu_item('APIwesomeAdmin');
		}
	}

	/**
	 *	The process to automatically construct data object output configurations, executed on project build.
	 */

	public function requireDefaultRecords() {

		parent::requireDefaultRecords();

		// Grab the list of data objects that have been completely removed.

		foreach(DB::table_list() as $table) {

			// Delete existing output configurations for these data objects.

			if(!class_exists($table)) {
				$existing = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', array(
					'IsFor = ?' => $table
				));
				$this->deleteConfiguration($table, $existing);
			}
		}

		// Grab the list of all data object types, along with any inclusions/exclusions defined.

		$objects = ClassInfo::subclassesFor('DataObject');
		$inclusions = self::$custom_inclusions;
		$exclusions = array_unique(array_merge(self::$exclusions, self::$custom_exclusions));

		// Check existing output configurations for these data objects.

		foreach($objects as $object) {
			$existing = DataObjectOutputConfiguration::get_one('DataObjectOutputConfiguration', array(
				'IsFor = ?' => $object
			));

			// Delete existing output configurations for invalid data objects, or for those excluded.

			if($existing && (self::$disabled || (get_parent_class($object) !== 'DataObject') || ClassInfo::classImplements($object, 'TestOnly') || ((count($inclusions) > 0) && !in_array($object, $inclusions)) || ((count($inclusions) === 0) && in_array($object, $exclusions)))) {
				$this->deleteConfiguration($object, $existing);
			}

			// Add an output configuration for valid data objects.

			else if(!$existing && !self::$disabled && (get_parent_class($object) === 'DataObject') && !ClassInfo::classImplements($object, 'TestOnly') && (((count($inclusions) > 0) && in_array($object, $inclusions)) || ((count($inclusions) === 0) && !in_array($object, $exclusions)))) {
				$this->addConfiguration($object);
			}
		}
	}

	/**
	 *	Print the data object name associated with this configuration.
	 *
	 *	@return string
	 */

	public function getTitle() {

		// Add spaces between words, other characters and numbers.

		return ltrim(preg_replace(array(
			'/([A-Z][a-z]+)/',
			'/([A-Z]{2,})/',
			'/([_.0-9]+)/'
		), ' $0', $this->IsFor));
	}

	/**
	 *	Display CMS JSON/XML output visibility configuration.
	 */

	public function getCMSFields() {

		$fields = parent::getCMSFields();
		Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');

		// Hide the data object name and output visibility associated with this configuration.

		$fields->removeByName('IsFor');
		$fields->removeByName('APIwesomeVisibility');

		// Grab a single data object.

		if(DataObject::get_one($this->IsFor)) {

			// Grab the appropriate attributes for this data object.

			$class = is_subclass_of($this->IsFor, 'SiteTree') ? 'SiteTree' : (is_subclass_of($this->IsFor, 'File') ? 'File' : $this->IsFor);
			$columns = array();
			foreach(ClassInfo::subclassesFor($class) as $subclass) {

				// Prepend the table names.

				$subclassColumns = array();
				foreach(DataObject::database_fields($subclass) as $column => $type) {
					$subclassColumns["{$subclass}.{$column}"] = $type;
				}
				$columns = array_merge($columns, $subclassColumns);
			}
			array_shift($columns);
			$visibility = $this->APIwesomeVisibility ? explode(',', $this->APIwesomeVisibility) : null;

			// Display the check box fields for JSON/XML output visibility.

			$configuration = FieldGroup::create(
				'Visibility'
			)->addExtraClass('visibility');
			$iteration = 0;
			foreach($columns as $name => $type) {

				// Print the attribute name, including any relationships.

				$split = explode('.', $name);
				$printName = ((substr($name, strlen($name) - 2) === 'ID') && (count($split) === 2) && ClassInfo::exists(($split[0])) && Singleton($split[0])->hasMethod(substr($split[1], 0, -2))) ? substr($name, 0, -2) : $name;
				$printName = ltrim(preg_replace(array(
					'/([A-Z][a-z]+)/',
					'/([A-Z]{2,})/',
					'/([_.0-9]+)/'
				), ' $0', $printName));

				// Set an already existing attribute visibility.

				$configuration->push(CheckboxField::create(
					str_replace('.', '-', "{$name}.APIwesomeVisibility"),
					"Display <strong>{$printName}</strong>?",
					(count($visibility) === count($columns)) && (isset($visibility[$iteration])) ? $visibility[$iteration] : 0
				));
				$iteration++;
			}
			$fields->addFieldToTab('Root.Main', $configuration);
		}
		else {

			// Display a notice that data objects should first be created.

			$fields->removeByName('CallbackFunction');
			$name = $this->getTitle();
			$fields->addFieldToTab('Root.Main', LiteralField::create(
				'ConfigurationNotice',
				"<p class='apiwesome notice'><strong>No {$name}s Found</strong></p>"
			));
		}
		$this->extend('updateDataObjectOutputConfigurationCMSFields', $fields);
		return $fields;
	}

	/**
	 *	Save the JSON/XML output visibility customisation for each associated data object.
	 */

	public function onBeforeWrite() {

		parent::onBeforeWrite();

		// Append the visibility customisation to a string.

		$visibility = '';
		foreach($this->record as $name => $value) {
			if(strrpos($name, 'APIwesomeVisibility')) {
				$value = $value ? $value : 0;
				$visibility .= "{$value},";
			}
		}

		// Write this visibility customisation string.

		$this->APIwesomeVisibility = rtrim($visibility, ',');
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
