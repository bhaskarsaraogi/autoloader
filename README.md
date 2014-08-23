autoloader [![Build Status](https://travis-ci.org/collectivism/autoloader.svg?branch=master)](https://travis-ci.org/collectivism/autoloader)
==========

## Installation

Add this to your `composer.json` and run `composer install`.

```json
  "require": {
    "collectivism/autoloader": "dev-master"
  }
```

## Usage

```php
use \Collectivism\Autoloader;

$autoloader = Autoloader::getInstace();

$classMap = array(
  'Namespace1\\SubNamespace1' => __DIR__ .
  '/Namespace1/SubNamespace1',
  'Namespace2\\SubNamespace2' => __DIR__ .
  '/Namespace2/SubNamespace2',
);

$autoloader->register($classMap);
```
