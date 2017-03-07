<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use SPSS\Sav\Writer;
use SPSS\Sav\Record;

class SavTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    public $file = 'tmp/data.sav';

    /**
     * @var array
     */
    public $baseData = [
        'header'    => [
//            'recType'     => '$FL2',
            'prodName'     => '@(#) SPSS DATA FILE test',
            'layoutCode'   => 2,
            'compression'  => 1,
            'weightIndex'  => 0,
            'bias'         => 100,
            'creationDate' => '13 Feb 89',
            'creationTime' => '13:13:13',
            'fileLabel'    => 'test file',
        ],
        'variables' => []
    ];

    /**
     * @return array
     */
    public function testWrite()
    {
        $data = $this->generateRandomData();
        $writer = new Writer($data);
        $writer->save($this->file);
        $this->assertFileExists($this->file);
        return $data;
    }

    /**
     * @depends testWrite
     * @param array $data
     */
    public function testRead(array $data)
    {
        $reader = Reader::fromFile($this->file);
        foreach ($data['header'] as $key => $value) {
            $this->assertEquals($reader->header->{$key}, $value);
        }
        $index = 0;
        foreach ($data['variables'] as $var) {
            /** @var Record\Variable $_var */
            $_var = $reader->variables[$index];
            $this->assertEquals($var['name'], $_var->name);
            $this->assertEquals($var['label'], $_var->label);
            $this->assertEquals($var['decimals'], $_var->print[0]);
            $this->assertEquals($var['format'], $_var->print[2]);
            foreach ($var['data'] as $case => $value) {
                $this->assertEquals($value, $reader->data[$case][$index]);
            }
            $index += $var['width'] > 0 ? Record\Variable::widthToOcts($var['width']) : 1;
        }
    }

    /**
     * @return array
     */
    public function generateRandomData()
    {
        $data = $this->baseData;
        $data['header']['nominalCaseSize'] = 0;
        $data['header']['casesCount'] = mt_rand(2, 10);
        $count = mt_rand(1, 5);
        for ($i = 0; $i < $count; $i++) {
            $isNumeric = rand(0, 1);
            $var = [
                'name'    => 'VAR' . $i,
                'label'   => 'Label - ' . $i,
                'width'   => 0,
                'format'  => 1,
                'columns' => mt_rand(0, 100),
                'align'   => mt_rand(0, 2),
                'measure' => mt_rand(1, 3),
                'data'    => [],
            ];
            if ($isNumeric) {
                $var['decimals'] = mt_rand(0, 2);
                $var['format'] = 5;
                for ($c = 0; $c < $data['header']['casesCount']; $c++) {
                    $var['data'][$c] = mt_rand(1, 99999) . '.' . mt_rand(1, 99999);
                }
            } else {
                $var['width'] = mt_rand(2, 1500);
                $var['decimals'] = 0;
                for ($c = 0; $c < $data['header']['casesCount']; $c++) {
                    $var['data'][$c] = $this->generateRandomString(mt_rand(0, $var['width']));
                }
            }
            $data['header']['nominalCaseSize'] += Record\Variable::widthToOcts($var['width']);
            $data['variables'][] = $var;
        }
        return $data;
    }

    /**
     * @param int $length
     * @return string
     */
    private function generateRandomString($length = 10)
    {
        $characters = ' 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}