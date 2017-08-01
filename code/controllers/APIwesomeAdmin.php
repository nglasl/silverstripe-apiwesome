<?php

/**
 *	APIwesome CMS interface for managing JSON/XML output configuration of an individual data object type.
 *	@author Nathan Glasl <nathan@symbiote.com.au>
 */

class APIwesomeAdmin extends ModelAdmin {

	private static $managed_models = 'DataObjectOutputConfiguration';

	private static $menu_title = 'JSON/XML';

	private static $menu_description = 'The <strong>JSON/XML</strong> feed will only be available to data objects with attribute visibility set through here. All data objects are included by default, unless exclusions or inclusions have explicitly been defined.';

	private static $url_segment = 'json-xml';

	/**
	 *	Update the custom summary fields to be sortable, and remove the add functionality.
	 */

	public function getEditForm($ID = null, $fields = null) {

		$form = parent::getEditForm($ID, $fields);
		$gridfield = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
		$configuration = $gridfield->getConfig();
		$configuration->getComponentByType('GridFieldSortableHeader')->setFieldSorting(array(
			'getTitle' => 'IsFor'
		));
		$configuration->removeComponentsByType('GridFieldAddNewButton');
		return $form;
	}

}
