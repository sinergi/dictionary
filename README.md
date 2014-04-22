Dictionary
==========

[![Build Status](https://travis-ci.org/sinergi/dictionary.svg)](https://travis-ci.org/sinergi/dictionary)


Localization and text management library for PHP.

## Requirements

This library uses PHP 5.4+.

## Installation

It is recommended that you install the Dictionary library [through composer](http://getcomposer.org/). To do so, add the following lines to your ``composer.json`` file.

```json
{
    "require": {
       "sinergi/dictionary": "dev-master"
    }
}
```

## Usage

Setup the Dictionary class with the path to your text files:

```php
use Sinergi\Dictionary\Dictionary;

$language = 'en';
$directory = __DIR__ . "/examples";

$dictionary = new Dictionary(
    $language,
    $directory
);
```

You can then use the dictionary like this:

```php
$dictionary['example']['title'];
```

## Examples

See more examples in the [examples folder](https://github.com/sinergi/dictionary/tree/master/examples).

Example of a dictionary file:

```php
return [
    'title' => "This is an example"
];
```