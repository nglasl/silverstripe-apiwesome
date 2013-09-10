# APIwesome

	A module for SilverStripe which will automatically create customisable JSON/XML feeds for your data
	objects.

## Requirement

* SilverStripe 3.0.X

## Getting Started

* Place the module under your root project directory.
* Define any custom JSON/XML data object exclusions/inclusions through project configuration.
* `/dev/build`
* Select `JSON/XML Configuration` through the CMS.
* Configure attribute visibility.
* `/apiwesome/retrieve/data-object-name/json` or `/apiwesome/retrieve/data-object-name/xml`

## Functionality

### Data Object Exclusions/Inclusions

* All data objects are included by default (excluding core), unless inclusions have explicitly been defined.

```php
DataObjectOutputConfiguration::customise_data_objects('exclude', array(
	'DataObjectName'
));
```

```php
DataObjectOutputConfiguration::customise_data_objects('include', array(
	'DataObjectName'
));
```

### Attribute Visibility

* Customisation will be required before your data object JSON/XML is available.
* Relationships are also displayed in JSON/XML using recursive attribute visibility on `has_one` relationships.

### JSON/XML Output

* Both available using different URL parameters.
* Preview JSON/XML available under model admin of your data objects.

### Development

```php
$service = Singleton('APIwesomeService');
```

* This module may also be used for custom developer JSON/XML functionality using the service controller methods available.

```php
$JSON = $service->retrieve('DataObjectName', 'JSON');
```

```php
$XML = $service->retrieve('DataObjectName', 'XML');
```

* This API is also used to parse incoming JSON/XML from another APIwesome instance, and return the appropriate data objects list. Therefore this can be used as both an API and an external connector between multiple projects.

```php
$objects = $service->parseJSON($JSON);
```

```php
$objects = $service->parseXML($XML);
```

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
