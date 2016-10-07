# Ride: Template Library (PHP)

PHP engine for the template library of the PHP Ride framework.

## Code Sample

Check this code sample to see how to initialize this library:

```php
use ride\library\template\engine\PhpEngine;

function createPhpTemplateEngine(System $system) {
    $engine = new PhpEngine($system->getFileBrowser(), 'view/php');
    
    return $engine;
}
```

### Implementations

You can check the related implementations of this library:
- [ride/app-template-php](https://github.com/all-ride/ride-app-template-php)
- [ride/lib-template](https://github.com/all-ride/ride-lib-template)

## Installation

You can use [Composer](http://getcomposer.org) to install this library.

```
composer require ride/lib-template-php
```
