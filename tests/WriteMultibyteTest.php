<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use SPSS\Sav\Writer;

class WriteMultibyteTest extends TestCase
{   
    public function setUp()
    {
        parent::setUp();
        $this->filename = __DIR__ . '/mbtest.sav';
    }
    
    public function testMultiByteLabel()
    {
        $data = [
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
                    'label'  => '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901233á',
                    'format' => 5,
                    'values' => [
                        1 => 'Panel',
                    ],
                ],
            ],
        ];
        $writer = new Writer($data);

        $writer->save($file);
        $reader = Reader::fromFile($file)->read();
        
        // Sort name
        $this->assertEquals($data['variables'][0]['label'], $reader->variables[0]->label);
        
        // Long name
        $this->assertEquals(mb_substr($data['variables'][1]['label'],0,-1), $reader->variables[1]->label);
    }
    
    public function tearDown()
    {
        unlink($this->filename);
    }
}
