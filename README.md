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

The JSON/XML feed will only be available to a data object with attribute visibility. The `has_one` relationships may also be displayed, where visibility is determined recursively.

### JSON/XML Output

The JSON/XML feed is not only available by request, but also by preview through the appropriate model admin of your data objects.

### Development

```php
$service = Singleton('APIwesomeService');
```

The service methods available may also be functionally called by developers to generate JSON/XML.

```php
$JSON = $service->retrieve('DataObjectName', 'JSON');
```

```php
$XML = $service->retrieve('DataObjectName', 'XML');
```

They may also be used to parse JSON/XML from another project's APIwesome module, returning the appropriate data object list. Therefore, this module may be used as both an API and external connector between multiple instances of projects.

```php
$objects = $service->parseJSON($JSON);
```

```php
$objects = $service->parseXML($XML);
```

## Maintainer Contact

	Nathan Glasl, nathan@silverstripe.com.au
