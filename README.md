SPSS - php implementation
====

## Requirements
* PHP 5.3.0 and up.

##Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist tiamo/spss "*"
```

or add

```
"tiamo/spss": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Reader example:
```php
$reader = \SPSS\Reader::fromFile('path/to/file.sav');
```
or
```php
$reader = \SPSS\Reader::fromString(file_get_contents('path/to/file.sav'));
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
            'creationDate' => '13 Feb 89',
            'creationTime' => '13:13:13',
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

## License
Licensed under the [MIT license](http://opensource.org/licenses/MIT).