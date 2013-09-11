# APIwesome (JSON/XML API)

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

## Overview

### Data Object Exclusions/Inclusions

All data objects are included by default (excluding core), unless inclusions have explicitly been defined.

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

### Attribute Visibility Customisation

The JSON/XML feed will only be available to data objects with attribute visibility set through the CMS. Any `has_one` relationships may be displayed, where attribute visibility is determined recursively.

### Output

The JSON/XML feed may also be previewed through the appropriate model admin of your data object.

### Developer Functionality

Accessing the service:

```php
$service = Singleton('APIwesomeService');
```

The methods available may be programmatically called to generate JSON:

```php
$JSON = $service->retrieve('DataObjectName', 'JSON');
```

```php
$objects = DataObjectName::get()->toNestedArray();
$JSON = $service->retrieveJSON($objects);
```

XML:

```php
$XML = $service->retrieve('DataObjectName', 'XML');
```

```php
$objects = DataObjectName::get()->toNestedArray();
$XML = $service->retrieveXML($objects);
```

They may also be used to parse JSON/XML from another APIwesome instance. Therefore, this module may be used as both an API and external connector between multiple projects.

```php
$objects = $service->parseJSON($JSON);
```

```php
$objects = $service->parseXML($XML);
```

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
