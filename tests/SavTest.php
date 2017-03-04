<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use \SPSS\Sav\Writer;

class SavTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    public $file = 'tmp/data.sav';

    /**
     * @var array
     */
    public $data = [
        'header'    => [
            'prodName'     => '@(#) IBM SPSS STATISTICS 64-bit Macintosh 23.0.0.0',
            'creationDate' => '13 Feb 89',
            'creationTime' => '13:13:13',
            'fileLabel'    => 'test file',
        ],
        'variables' => []
    ];

    public function setUp()
    {
        parent::setUp();

        // add numeric variable
        $this->data['variables'][] = [
            'name'     => 'num1',
            'width'    => 0,
            'decimals' => 2,
            'format'   => 5,
            'columns'  => 10,
            'align'    => 1,
            'measure'  => 1,
            'data'     => [1.11, 2.22, 3.33]
        ];
    }

    public function testWrite()
    {
        $writer = new Writer($this->data);
        $writer->save($this->file);
        $this->assertFileExists($this->file);
    }

    /**
     * @after testWriteSav
     */
    public function testRead()
    {
        $reader = Reader::fromFile($this->file);

        $this->assertTrue($reader->header->prodName == $this->data['header']['prodName'], 'header->prodName');
        $this->assertTrue($reader->header->creationDate == $this->data['header']['creationDate'], 'header->creationDate');
        $this->assertTrue($reader->header->creationTime == $this->data['header']['creationTime'], 'header->creationTime');
//        foreach ($reader->variables as $var) {
//            print_r($var);
//            exit;
//        }
        $this->assertFileExists($this->file);
    }
}