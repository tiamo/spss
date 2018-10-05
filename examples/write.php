<?php

use SPSS\Sav\Variable;

require __DIR__ . '/../vendor/autoload.php';

$file = __DIR__ . '/data.sav';

$writer = new \SPSS\Sav\Writer([
        'header' => [
            'prodName' => '@(#) IBM SPSS STATISTICS 64-bit Macintosh 23.0.0.0',
            'creationDate' => '05 Oct 18',
            'creationTime' => '01:36:53',
            'weightIndex' => 0,
            // 'casesCount' => 3,
            // 'compression' => 1,
            // 'bias' => 100,
            // 'fileLabel' => '',
        ],
        // 'info' => [
        //     'machineInteger' => [
        //         'machineCode' => 720,
        //         'version' => [23, 0, 0],
        //     ],
        //     'machineFloatingPoint' => [
        //         'sysmis' => -1.7976931348623157e+308,
        //         'highest' => 1.7976931348623157e+308,
        //         'lowest' => -1.7976931348623155e+308,
        //     ],
        // ],
        'variables' => [
            [
                'name' => 'aaa',
                'format' => Variable::FORMAT_TYPE_F,
                'width' => 4,
                'decimals' => 2,
                'label' => 'test',
                'values' => [
                    222 => 'foo',
                    '13.22' => 'bar',
                ],
                // 'missing' => [],
                'columns' => 16,
                'alignment' => Variable::ALIGN_RIGHT,
                'measure' => Variable::MEASURE_SCALE,
                'attributes' => [
                    '$@Role' => Variable::ROLE_PARTITION,
                ],
                'data' => [1, 1, 1],
            ],
            [
                'name' => 'bbbb_bbbbbb12',
                'format' => Variable::FORMAT_TYPE_A,
                'width' => 28,
                // 'decimals' => 0,
                'label' => 'test',
                'values' => [
                    'm' => 'male',
                    'f' => 'female',
                ],
                // 'missing' => [],
                'columns' => 8,
                'alignment' => Variable::ALIGN_LEFT,
                'measure' => Variable::MEASURE_NOMINAL,
                'attributes' => [
                    '$@Role' => Variable::ROLE_SPLIT,
                ],
                'data' => ['foo', 'bar', 'baz'],
            ],
            [
                'name' => 'BBBB_BBBBBB13',
                'format' => Variable::FORMAT_TYPE_COMMA,
                'width' => 8,
                'decimals' => 2,
                // 'label' => 'test',
                // 'values' => [
                //     1 => 'test'
                // ],
                // 'missing' => [],
                'columns' => 8,
                'alignment' => Variable::ALIGN_RIGHT,
                'measure' => Variable::MEASURE_NOMINAL,
                'attributes' => [
                    '$@Role' => Variable::ROLE_INPUT,
                ],
                'data' => [1, 1, 1],
            ],
        ],
    ]
);

$writer->save($file);
