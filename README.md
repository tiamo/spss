# SPSS / PSPP

A PHP library for reading and writing SPSS / PSPP .sav data files.

VERSION 2.1.0 ([CHANGELOG](CHANGELOG.md))

[![Build Status](https://travis-ci.org/tiamo/spss.svg?branch=master)](https://travis-ci.org/tiamo/spss)

## Requirements

* PHP 5.5.0 and up
* mbstring extension
* bcmath extension

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require tiamo/spss
```

or add

```
"tiamo/spss": "*"
```

to the require section of your `composer.json` [file](https://packagist.org/packages/tiamo/spss)
or download from [here](https://github.com/tiamo/spss/releases).

## Usage

Reader example:

```php
// Initialize reader
$reader = \SPSS\Reader::fromFile('path/to/file.sav');

// Read header data
$reader->readHeader();
// var_dump($reader->header);

// Read full data
$reader->read();
// var_dump($reader->variables);
// var_dump($reader->valueLabels);
// var_dump($reader->documents);
// var_dump($reader->data);
```
or
```php
$reader = \SPSS\Reader::fromString(file_get_contents('path/to/file.sav'))->read();
```

Writer example:

```php
$writer = new \SPSS\Writer([
    'header' => [
            'prodName'     => '@(#) SPSS DATA FILE test',
            'layoutCode'   => 2,
            'compression'  => 1,
            'weightIndex'  => 0,
            'bias'         => 100,
            'creationDate' => '01 Feb 01',
            'creationTime' => '01:01:01',
    ],
    'variables' => [
        [
                'name'     => 'VAR1',
                'width'    => 0,
                'decimals' => 0
                'format'   => 5,
                'columns'  => 50,
                'align'    => 1,
                'measure'  => 1,
                'data'     => [
                    1, 2, 3
                ],
        ],
        ...
    ]
]);
```

## Changelog

Please have a look in [CHANGELOG](CHANGELOG.md)

## License

Licensed under the [MIT license](http://opensource.org/licenses/MIT).
