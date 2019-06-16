<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use SPSS\Sav\Variable;
use SPSS\Sav\Writer;

class WriteMultibyteTest extends TestCase
{

    public function testMultiByteLabel()
    {
        $data   = [
            'header'    => [
                'prodName'     => '@(#) IBM SPSS STATISTICS',
                'layoutCode'   => 2,
                'creationDate' => date('d M y'),
                'creationTime' => date('H:i:s'),
            ],
            'variables' => [
                [
                    'name'   => 'longname_longerthanexpected',
                    'label'  => 'Data zákończenia',
                    'width'  => 16,
                    'format' => 1,
                ],
                [
                    'name'   => 'ccc',
                    'label'  => 'áá345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901233á',
                    'format' => 5,
                    'values' => [
                        1 => 'áá345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901233á',
                    ],
                ],
            ],
        ];
        $writer = new Writer($data);

        $buffer = $writer->getBuffer();
        $buffer->rewind();

        $reader = Reader::fromString($buffer->getStream())->read();

        // Short variable label
        $this->assertEquals($data['variables'][0]['label'], $reader->variables[0]->label);

        // Long variable label
        $this->assertEquals(mb_substr($data['variables'][1]['values'][1], 0, -2, 'UTF-8'), $reader->variables[1]->label);
        
        // Long value label
        $this->assertEquals(mb_substr($data['variables'][1]['label'], 0, -2, 'UTF-8'), $reader->valueLabels[0]->labels[0]['label']);
    }
    
    /**
     * ISSUE #20
     * 
     * Chinese value labels seem to work fine, but free text does not work
     */
    public function testChinese()
    {
        $input = [
            'header'    => [
                'prodName'     => '@(#) IBM SPSS STATISTICS 64-bit Macintosh 23.0.0.0',
                'creationDate' => '05 Oct 18',
                'creationTime' => '01:36:53',
                'weightIndex'  => 0,
            ],
            'variables' => [
                [
                    'name'       => 'test1',
                    'format'     => Variable::FORMAT_TYPE_F,
                    'width'      => 4, 
                    'decimals'   => 2,
                    'label'      => 'test',
                    'values'     => [
                        1 => '1测试中文标签1',
                        2 => '2测试中文标签2',
                    ],
                    'missing'    => [],
                    'columns'    => 5,
                    'alignment'  => Variable::ALIGN_RIGHT,
                    'measure'    => Variable::MEASURE_SCALE,
                    'attributes' => [
                        '$@Role' => Variable::ROLE_PARTITION,
                    ],
                    'data'       => [1, 1, 1],
                    ],
                [
                    'name'       => 'test2',
                    'format'     => Variable::FORMAT_TYPE_A,
                    'width'      => 100,
                    'label'      => 'test',
                    'columns'    => 100,
                    'alignment'  => Variable::ALIGN_LEFT,
                    'measure'    => Variable::MEASURE_NOMINAL,
                    'attributes' => [
                        '$@Role' => Variable::ROLE_SPLIT,
                    ],
                    'data'       => [
                        '测试中文数据1',
                        '测试中文数据2',
                        '测试中文数据3'
                    ],
                ],
            ],
        ];
        
        $writer = new Writer($input);
        
        // Uncomment if you want to really save and check the resulting file in SPSS
        //$writer->save('chinese1.sav');
        $buffer = $writer->getBuffer();
        $buffer->rewind();

        $reader = Reader::fromString($buffer->getStream())->read();
        $expected[0][0] = $input['variables'][0]['data'][0];
        $expected[0][1] = $input['variables'][1]['data'][0];
        $expected[1][0] = $input['variables'][0]['data'][1];
        $expected[1][1] = $input['variables'][1]['data'][1];
        $expected[2][0] = $input['variables'][0]['data'][2];
        $expected[2][1] = $input['variables'][1]['data'][2];
        $this->assertEquals($expected, $reader->data);
    }

}
