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

        $data = array(
            'header' => array(
                'prodName' => '@(#) IBM SPSS STATISTICS',
                'layoutCode' => 2,
                'creationDate' => '08 May 19',
                'creationTime' => '12:22:16',
            ),
            'variables' => array(
                array(
                    'name' => 'long',
                    'label' => 'long label',
                    'width' => 300,
                    'format' => Variable::FORMAT_TYPE_A,
                    'attributes' => array(
                        '$@Role' => Variable::ROLE_INPUT,
                    ),
                    'data' => array(
                        $firstLong,
                        $secondLong,
                    ),
                ),
                array(
                    'name' => 'short',
                    'label' => 'short label',
                    'format' => Variable::FORMAT_TYPE_A,
                    'width' => 8,
                    'attributes' => array(
                        '$@Role' => Variable::ROLE_INPUT,
                    ),
                    'data' => array(
                        '12345678',
                        'abcdefgh',
                    ),
                ),
            ),
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $writer = new Writer($data);

        // Uncomment if you want to really save and check the resulting file in SPSS
        // $writer->save('longString.sav');

        $buffer = $writer->getBuffer();
        $buffer->rewind();

        $reader = Reader::fromString($buffer->getStream())->read();

        $expected = array();
        $expected[0][0] = $data['variables'][0]['data'][0];
        $expected[0][1] = $data['variables'][1]['data'][0];
        $expected[1][0] = $data['variables'][0]['data'][1];
        $expected[1][1] = $data['variables'][1]['data'][1];

        $this->assertEquals($expected, $reader->data);
    }
}
