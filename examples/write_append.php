<?php

require __DIR__ . '/../vendor/autoload.php';

DEFINE('FILE_SAV', __DIR__ . '/data_append.sav');

$variables = [
    [
        'name' => 'ccc',
        'format' => 5,
        'data' => [
            9, 8, 7, 6, 5, 4
        ],
    ],
];

$writer = new \SPSS\Sav\Writer([
    'cases_count_total' => 10, # first time you need this
    'header' => [
        'prodName' => '@(#) IBM SPSS STATISTICS',
        'layoutCode' => 2,
        #'compression'  => 1,
        #'creationDate' => date('d M y'),
        #'creationTime' => date('H:i:s'),
    ],
    'variables' => $variables
]);

# first time, save
$writer->save(FILE_SAV);

# append once
appendToSPSS([
    [
        'name' => 'ccc',
        'format' => 5,
        'data' => [
            3
        ],
    ],
]);

# append twice
appendToSPSS([
    [
        'name' => 'ccc',
        'format' => 5,
        'data' => [
            2, 1, 0
        ],
    ],
]);

# no need cases_count_total, because we just append the Data Record only
function appendToSPSS($variables)
{
    $writer = new \SPSS\Sav\Writer([
        'header' => [
            'prodName' => '@(#) IBM SPSS STATISTICS',
            'layoutCode' => 2,
            #'compression'  => 1,
            #'creationDate' => date('d M y'),
            #'creationTime' => date('H:i:s'),
        ],
        'variables' => $variables
    ]);

    $writer->append(FILE_SAV);

    return true;
}
