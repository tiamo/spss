<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use SPSS\Sav\Record;
use SPSS\Sav\Writer;
use SPSS\Utils;

class SavRandomReadWriteTest extends TestCase
{
    public function provider()
    {
        $header = [
            'recType'         => Record\Header::NORMAL_REC_TYPE,
            'prodName'        => '@(#) SPSS DATA FILE',
            'layoutCode'      => 2,
            'nominalCaseSize' => 0,
            'casesCount'      => mt_rand(10, 100),
            'compression'     => 1,
            'weightIndex'     => 0,
            'bias'            => 100,
            'creationDate'    => date('d M y'),
            'creationTime'    => date('H:i:s'),
            'fileLabel'       => 'test read/write',
        ];

        $documents = [
            $this->generateRandomString(mt_rand(5, Record\Document::LENGTH)),
            $this->generateRandomString(mt_rand(5, Record\Document::LENGTH)),
        ];

        $variables = [];

        // Generate random variables

        $count = 1; // mt_rand(1, 20);
        for ($i = 0; $i < $count; $i++) {
            $var = $this->generateVariable([
                    'id'         => $this->generateRandomString(mt_rand(2, 100)),
                    'casesCount' => $header['casesCount'],
                ]
            );
            $header['nominalCaseSize'] += Utils::widthToOcts($var['width']);
            $variables[] = $var;
        }

        yield [compact('header', 'variables', 'documents')];

        $header['casesCount'] = 5;
        for ($i = 0; $i < 100; $i++) {
            $variable = $this->generateVariable([
                'id'         => $this->generateRandomString(mt_rand(2, 100)),
                'casesCount' => $header['casesCount'],
            ]);
            $header['nominalCaseSize'] = Utils::widthToOcts($variable['width']);
            yield [
                [
                    'header'    => $header,
                    'variables' => [$variable],
                    'documents' => $documents,
                ],
            ];
        }
    }

    /**
     * @dataProvider provider
     *
     * @param array $data
     *
     * @throws \Exception
     */
    public function testWriteRead($data)
    {
        $writer = new Writer($data);

        $buffer = $writer->getBuffer();
        $buffer->rewind();

        $reader = Reader::fromString($buffer->getStream())->read();

        $this->checkHeader($data['header'], $reader);

        if ($data['documents']) {
            foreach ($data['documents'] as $key => $doc) {
                $this->assertEquals($doc, $reader->documents[$key], 'Invalid document line.');
            }
        }

        if (isset($reader->info[Record\Info\VeryLongString::SUBTYPE])) {
            $veryLongStrings = $reader->info[Record\Info\VeryLongString::SUBTYPE]->toArray();
        } else {
            $veryLongStrings = [];
        }

        $index = 0;

        foreach ($data['variables'] as $var) {
            /** @var Record\Variable $readVariable */
            $readVariable = $reader->variables[$index];

            $this->assertEquals($var['label'], $readVariable->label);
            $this->assertEquals($var['format'], $readVariable->print[1]);
            $this->assertEquals($var['decimals'], $readVariable->print[3]);

            // Check variable data

            foreach ($var['data'] as $case => $value) {
                $this->assertEquals($value, $reader->data[$case][$index]);
            }

            $index += isset($veryLongStrings[$readVariable->name]) ?
                Utils::widthToSegments($veryLongStrings[$readVariable->name]) : 1;
        }

        // TODO: valueLabels
        // TODO: info
    }
}
