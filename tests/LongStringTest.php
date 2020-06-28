<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use SPSS\Sav\Variable;
use SPSS\Sav\Writer;

class LongStringTest extends TestCase
{
    public function testLongString()
    {
        $firstLong = str_repeat('1234567890', 30);
        $secondLong = str_repeat('abcdefghij', 30);

        $data = [
            'header' => [
                'prodName' => '@(#) IBM SPSS STATISTICS',
                'layoutCode' => 2,
                'creationDate' => '08 May 19',
                'creationTime' => '12:22:16',
            ],
            'variables' => [
                [
                    'name' => 'long',
                    'label' => 'long label',
                    'width' => 300,
                    'format' => Variable::FORMAT_TYPE_A,
                    'attributes' => [
                        '$@Role' => Variable::ROLE_INPUT,
                    ],
                    'data' => [
                        $firstLong,
                        $secondLong,
                    ],
                ],
                [
                    'name' => 'short',
                    'label' => 'short label',
                    'format' => Variable::FORMAT_TYPE_A,
                    'width' => 8,
                    'attributes' => [
                        '$@Role' => Variable::ROLE_INPUT,
                    ],
                    'data' => [
                        '12345678',
                        'abcdefgh',
                    ],
                ],
            ],
        ];

        $writer = new Writer($data);

        // Uncomment if you want to really save and check the resulting file in SPSS
        // $writer->save('longString.sav');

        $buffer = $writer->getBuffer();
        $buffer->rewind();

        $reader = Reader::fromString($buffer->getStream())->read();

        $expected = [];
        $expected[0][0] = $data['variables'][0]['data'][0];
        $expected[0][1] = $data['variables'][1]['data'][0];
        $expected[1][0] = $data['variables'][0]['data'][1];
        $expected[1][1] = $data['variables'][1]['data'][1];

        $this->assertEquals($expected, $reader->data);
    }
}
