# DOCUMENTATION

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
                'name'     => 'VAR1', # For UTF-8, 64 / 3 = 21, mb_substr($var1, 0, 21);
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
