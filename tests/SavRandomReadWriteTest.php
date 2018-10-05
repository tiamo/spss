<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use SPSS\Sav\Record;
use SPSS\Sav\Writer;
use SPSS\Utils;

class SavRandomReadWriteTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     * @param array $data
     * @throws \Exception
     */
    public function testWriteAndRead($data)
    {
        $writer = new Writer($data);

        // $writer->save(__DIR__ . '/../examples/data.sav');

        $buffer = $writer->getBuffer();
        $buffer->rewind();

        // $stream = $buffer->getStream();
        // $this->assertContains(stream_get_contents($stream, 4), [
        //         Record\Header::NORMAL_REC_TYPE,
        //         Record\Header::ZLIB_REC_TYPE,
        //     ]
        // );
        //

        $reader = Reader::fromString($buffer->getStream())->read();

        $this->checkHeader($data['header'], $reader);

        if ($data['documents']) {
            foreach ($data['documents'] as $key => $doc) {
                $this->assertEquals($doc, $reader->documents[$key], 'Invalid document line.');
            }
        }

        $index = 0;
        foreach ($data['variables'] as $var) {
            /** @var Record\Variable $_var */
            $_var = $reader->variables[$index];

            // if (isset($reader->info[Record\Info\LongVariableNames::SUBTYPE])) {
            //     // ...
            // }
            //
            // if (mb_strlen($_var->label) > 8) {
            // }
            // $label = $_var->label;

            // TODO: long variables
            //  $this->assertEquals($var['name'], $_var->name);

            // var_dump($_var->print, $var);
            // exit;

            $this->assertEquals($var['label'], $_var->label);
            $this->assertEquals($var['format'], $_var->print[1]);
            $this->assertEquals($var['decimals'], $_var->print[3]);

            // TODO: data tests
            // Check variable data
            // foreach ($var['data'] as $case => $value) {
            //     $this->assertEquals($value, $reader->data[$case][$index],
            //         // sprintf('%s,%s - %s', $case, $index, $value)
            //         json_encode([
            //                 'case' => $case,
            //                 'index' => $index,
            //                 'value' => $value,
            //                 'prev' => @$reader->data[$case-1][$index],
            //                 'next' => @$reader->data[$case+1][$index],
            //                 // 'data' => $data['variables'],
            //             ]
            //         )
            //     );
            // }
            $index += $var['width'] > 0 ? Utils::widthToOcts($var['width']) : 1;
        }

        // TODO: valueLabels
        // TODO: info
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        $header = [
            'recType' => Record\Header::NORMAL_REC_TYPE,
            'prodName' => '@(#) SPSS DATA FILE',
            'layoutCode' => 2,
            'nominalCaseSize' => 0,
            'casesCount' => mt_rand(10, 100),
            'compression' => 1,
            'weightIndex' => 0,
            'bias' => 100,
            'creationDate' => date('d M y'),
            'creationTime' => date('H:i:s'),
            'fileLabel' => 'test read/write',
        ];

        $documents = [
            $this->generateRandomString(mt_rand(5, Record\Document::LENGTH)),
            $this->generateRandomString(mt_rand(5, Record\Document::LENGTH)),
        ];

        $variables = [];

        // Generate random variables

        $count = mt_rand(1, 20);
        for ($i = 0; $i < $count; $i++) {
            $var = $this->generateVariable([
                    'id' => $this->generateRandomString(mt_rand(2, 100)),
                    'casesCount' => $header['casesCount'],
                ]
            );
            $header['nominalCaseSize'] += Utils::widthToOcts($var['width']);
            $variables[] = $var;
        }

        return [
            [
                compact('header', 'variables', 'documents'),
            ],
        ];
    }
}
