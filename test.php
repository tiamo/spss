#!/usr/bin/php
<?php

require 'vendor/autoload.php';
date_default_timezone_set('UTC');

use SPSS\Buffer;
use SPSS\Sav\Reader;
use SPSS\Sav\Writer;

//$x = 1500;
//$y = 252;
//
//echo ceil($x / $y);
//echo (($x) + (($y) - 1)) / ($y);

//for($i = 0; $i< 7; $i++) {
//    echo \SPSS\Sav\Record\Variable::widthToBytes(1500) . PHP_EOL;
//}
//
//exit;

// test read data
//$reader = Reader::fromFile('tmp/test.sav');
//print_r($reader->variables);
//exit;


//$num = unpack('d', pack('A8', 'test asdas as '));
//print_r(unpack('A*', pack('d', $num[1])));
//exit;

$data = [
    'header'    => [
        'prodName'     => '@(#) IBM SPSS STATISTICS 64-bit Macintosh 23.0.0.0',
        'creationDate' => date('d M y'),
        'creationTime' => date('H:i:s'),
        'fileLabel'    => 'test',
//        'compression'  => 0,
//        'bias'         => 0,
    ],
    'variables' => [
        [
            'name'     => 'test1',
            'label'    => 'test label',
            'width'    => 16,
            'decimals' => 0,
            'format'   => 1,
            'values'   => [
                'a' => 'b',
                1   => 2
            ],
//            'missing'  => [
//                1, 2, 'aaaa'
//            ],
            'columns'  => 10,
            'align'    => 1,
            'measure'  => 1,
            'data'     => [
                '1111', '2222', '3333'
            ]
        ],
        [
            'name'     => 'test1',
            'width'    => 8,
            'decimals' => 0,
            'format'   => 1,
            'columns'  => 10,
            'align'    => 1,
            'measure'  => 1,
            'data'     => [
                'a', 'b', 'c'
            ]
        ],
        [
            'name'     => 'num1',
            'width'    => 0,
            'decimals' => 2,
            'format'   => 5,
            'values'   => [],
            'columns'  => 10,
            'align'    => 2,
            'measure'  => 1,
            'data'     => [
                1, 2, -1.7976931348623E+308, 4, 55.3343, 6, 7, 8, 9, 1111.11212
            ]
        ],
        [
            'name'     => 'TEST2',
            'label'    => 'test label',
            'width'    => 257,
            'decimals' => 0,
            'format'   => 1,
            'values'   => [
                'a' => 'b',
                1   => 2
            ],
//            'missing'  => [
//                1, 2, 'aaaa'
//            ],
            'columns'  => 10,
            'align'    => 1,
            'measure'  => 1,
            'data'     => [
                'Renaming variable with duplicate name', 'c', 'Renaming variable with duplicate nameRenaming variable with duplicate nameRenaming variable with duplicate name'
            ]
        ],
        [
            'name'     => 'num2',
            'width'    => 0,
            'decimals' => 2,
            'format'   => 5,
            'values'   => [],
            'columns'  => 10,
            'align'    => 2,
            'measure'  => 1,
            'data'     => [
                21, 2212, 1212
            ]
        ],
    ]
];

$writer = new Writer($data);
$writer->save('tmp/data.sav');

$reader = Reader::fromFile('tmp/data.sav');
print_r($reader->data);