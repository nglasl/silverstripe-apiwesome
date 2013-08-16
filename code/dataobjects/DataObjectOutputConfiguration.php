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

	public static $summary_fields = array(
		'printIsFor'
	);

	public static $field_labels = array(
		'printIsFor' => 'Is For'
	);

	public static $searchable_fields = array(
		'IsFor'
	);

	public static $default_sort = 'IsFor';

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
	
	// The list of custom data objects that we wish to ignore from everything.

	private static $custom_exclusions = array(
	);
	
	// The list of custom data objects that we wish to use from nothing.

	private static $custom_inclusions = array(
	);

	/**
	 *	Replace JSON/XML custom data object exclusions with inclusions, changing all data object visibility to hidden except for those defined.
	 *	NOTE: This will change the exclusions array definition for you, so you only need to update the data object names listed. This will require a project build.
	 *
	 *	@param array(string)
	 */

	public static function exclusions_to_inclusions($exclusions_to_inclusions) {

		// Search the contents of the base project configuration definitions.

		$files = array(BASE_PATH . '/mysite/local.conf.php', BASE_PATH . '/mysite/_config.php');
		foreach($files as $file) {
			$config = file_get_contents($file);

			// Make sure this project configuration has actually been set.

			if($exclusions_to_inclusions) {

					// Convert the exclusions array to an inclusions array.

					$config = str_replace('DataObjectOutputConfiguration::exclusions_to_inclusions(true);', 'DataObjectOutputConfiguration::exclusions_to_inclusions(false);', $config);
					$config = str_replace('DataObjectOutputConfiguration::add_custom_exclusions', 'DataObjectOutputConfiguration::add_custom_inclusions', $config);
					file_put_contents($file, $config);
			}

			// If a definition for both exclusions and inclusions are found, convert them all to inclusions.

			else if(strpos($config, 'DataObjectOutputConfiguration::add_custom_exclusions') && strpos($config, 'DataObjectOutputConfiguration::add_custom_inclusions')) {
					$config = str_replace('DataObjectOutputConfiguration::add_custom_exclusions', 'DataObjectOutputConfiguration::add_custom_inclusions', $config);
					file_put_contents($file, $config);
			}
		}
	}

	/**
	 *	Add the custom data object JSON/XML exclusions from everything.
	 *
	 *	@param array(string)
	 */

	public static function add_custom_exclusions($custom_exclusions) {

		if(is_array($custom_exclusions)) {
			self::$custom_exclusions = array_unique(array_merge(self::$custom_exclusions, $custom_exclusions));
		}
	}

	/**
	 *	Add the custom data object JSON/XML inclusions from nothing.
	 *	NOTE: Changes all data object visibility to hidden except for those defined, effectively taking precedence over the custom exclusions.
	 *
	 *	@param array(string)
	 */

	public static function add_custom_inclusions($custom_inclusions) {

		if(is_array($custom_inclusions)) {
			self::$custom_inclusions = array_unique(array_merge(self::$custom_inclusions, $custom_inclusions));
		}
	}

	/**
	 *	Apply all required object extensions, which is executed on project build.
	 *	This will take into account any inclusions/exclusions that have been defined.
	 */

	public static function apply_required_extensions() {

		// Retrieve the list of objects that extend the base data object, along with any inclusions/exclusions that have been defined.

		$objects = ClassInfo::subclassesFor('DataObject');
		$inclusions = self::$custom_inclusions;
		$exclusions = array_unique(array_merge(self::$exclusions, self::$custom_exclusions));

		// Apply the required APIwesome extensions to each object considered valid.

		foreach($objects as $object) {

			// If there are custom inclusions, then disable the automatic JSON/XML configuration of all data objects.

			if(((count($inclusions) > 0) && is_subclass_of($object, 'DataObject') && in_array($object, $inclusions)) || ((count($inclusions) === 0) && is_subclass_of($object, 'DataObject') && !in_array($object, $exclusions))) {
				Object::add_extension($object, 'DataObjectOutputExtension');
			}
		}

		// Apply any remaining APIwesome extensions required.

		Object::add_extension('APIwesomeAdmin', 'APIwesomeAdminExtension');
	}

	/**
	 *	The process to automatically construct configurations for existing data objects, which is executed on project build.
	 *	This will take into account any inclusions/exclusions that have been defined.
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
				$this->deleteConfiguration($table, $existing);
			}
		}

		// Retrieve the list of valid data objects, along with any inclusions/exclusions that have been defined.

		$objects = ClassInfo::subclassesFor('DataObject');

		// If there are custom inclusions, then disable the automatic JSON/XML configuration of all data objects.

		$inclusions = self::$custom_inclusions;
		$exclusions = array_unique(array_merge(self::$exclusions, self::$custom_exclusions));
		foreach($objects as $object) {
			$existing = DataObjectOutputConfiguration::get()->filter(array('IsFor' => $object));

			// If a configuration is found for something no longer included, otherwise if a configuration is found for new custom exclusions.

			if(((count($inclusions) > 0) && is_subclass_of($object, 'DataObject') && !in_array($object, $inclusions)) || ((count($inclusions) === 0) && is_subclass_of($object, 'DataObject') && in_array($object, $exclusions))) {
				$this->deleteConfiguration($object, $existing);
			}

			// Else, if we find a new data object has been included, otherwise if we find a new data object that isn't excluded.

			else if(((count($inclusions) > 0) && is_subclass_of($object, 'DataObject') && in_array($object, $inclusions)) || ((count($inclusions) === 0) && is_subclass_of($object, 'DataObject') && !in_array($object, $exclusions))) {
				$this->addConfiguration($object, $existing);
			}
		}
	}

	/**
	 *	Update the CMS interface to allow customisation of JSON/XML output for individual data objects.
	 */

	public function getCMSFields() {

		$fields = parent::getCMSFields();
		Requirements::css(APIWESOME_PATH . '/css/apiwesome.css');

		// The data object that is assigned to this configuration must not be changed.

		$fields->removeByName('IsFor');

		// Retrieve the corresponding data object, if one exists.

		$objects = DataObject::get($this->IsFor)->sort('APIwesomeVisibility DESC');
		if($objects && $objects instanceof DataList && $objects->first()) {
			$object = $objects->first();

			// Retrieve the attributes for this data object.

			$columns = DataObject::database_fields($this->IsFor);
			array_shift($columns);
			$visibility = $object->APIwesomeVisibility ? explode(',', $object->APIwesomeVisibility) : null;

			// Add the check box field title.

			$fields->addFieldToTab('Root.Main', LiteralField::create(
				'VisibilityTitle',
				'<strong>Visibility</strong>'
			));

			// Construct the check box fields for JSON/XML visibility customisation.

			$iteration = 0;
			foreach($columns as $name => $type) {

				// Take the visibility attribute into account.

				if($name !== 'APIwesomeVisibility') {
					$printName = ltrim(preg_replace('/[A-Z]+[^A-Z]/', ' $0', $name));
					$fields->addFieldToTab('Root.Main', CheckboxField::create(
						"{$name}Visibility",
						"Display <strong>{$printName}</strong>?",
						(is_array($visibility) && isset($visibility[$iteration])) ? $visibility[$iteration] : 0
					));
					$iteration++;
				}
			}
		}
		else {

			// Notify the user that a data object of this type should first be created.

			$fields->removeByName('CallbackFunction');
			$name = $this->printIsFor();
			$fields->addFieldToTab('Root.Main', LiteralField::create(
				'ConfigurationNotification',
				"<p class='cms notification'><strong>No {$name}s Found</strong></p>"
			));
		}
		return $fields;
	}

	/**
	 *	Save the JSON/XML visibility customisation for each data object of this type.
	 */

	public function onAfterWrite() {

		parent::onAfterWrite();

		// Create a single variable to store the visibility customisation values.

		$visibility = '';
		foreach($this->record as $name => $value) {
			if(strrpos($name, 'Visibility')) {
				$value = $value ? $value : 0;
				$visibility .= "$value,";
			}
		}
		$visibility = rtrim($visibility, ',');

		// Save this visibility customisation.

		$objects = DataObject::get($this->IsFor);
		if($objects && $objects instanceof DataList) {
			foreach($objects as $object) {
				$object->APIwesomeVisibility = $visibility;
				$object->write();
			}
		}
	}

	/**
	 *	Print the data object name associated to this configuration.
	 *
	 *	@return string
	 */

	public function printIsFor() {

		// Add spaces between words, such that the result is readable.

		return ltrim(preg_replace('/[A-Z]+[^A-Z]/', ' $0', $this->IsFor));
	}

	/**
	 *	Remove an existing data object configuration on project build.
	 */

	private function deleteConfiguration($object, $existing) {

		// Remove these existing configurations.

		if($existing && $existing instanceof DataList && $existing->first()) {
			$existing->first()->delete();
			DB::alteration_message($object . ' JSON/XML Configuration', 'deleted');
		}
	}

	/**
	 *	Add a new data object configuration on project build.
	 */

	private function addConfiguration($object, $existing) {

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
