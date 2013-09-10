# APIwesome

A module for SilverStripe which will automatically create customisable JSON/XML feeds for your data objects.

## Requirement

* SilverStripe 3.0.X

## Getting Started

* Place the module under your root project directory.
* Define your custom JSON/XML data object exclusions/inclusions through project configuration files.
* <WEBSITE>`/dev/build`
* Select `JSON/XML Configuration` from the CMS.
* Configure visibility customisation.
* <WEBSITE>`/apiwesome/retrieve/<data-object-name>/json`
* <WEBSITE>`/apiwesome/retrieve/<data-object-name>/xml`

## Functionality

### Data Object Exclusion/Inclusion

* All data objects are included by default (excluding core), unless inclusions have been defined.

### Attribute Visibility

* Customisation will be required before your data object JSON/XML is available.
* Relationships are also displayed in JSON/XML using recursive attribute visibility on `has_one` relationships.

### JSON/XML Output

* Both available using different URL parameters.
* Preview JSON/XML available under model admin of your data objects.

```php
// JSON/XML retrieval code example:
Singleton('APIwesomeService')->retrieve('<DataObjectName>', '<OutputType>');
```

### Development

* This module may also be used for custom developer JSON/XML functionality using the service controller methods available.
* This API is also used to parse incoming JSON/XML from another APIwesome instance, and return the appropriate data objects list. Therefore this can be used as both an API and an external connector between multiple projects.

```php
// parse json example
Singleton('APIwesomeService')->parseJSON('<JSON>');
```

```php
// @parse xml example
Singleton('APIwesomeService')->parseXML('<XML>');
```

## Maintainer Contact

	Nathan Glasl <nathan@silverstripe.com.au>
