<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use SPSS\Sav\Record\Info\LongStringValueLabels;
use SPSS\Sav\Variable;
use SPSS\Sav\Writer;

class StringValueLabelsTest extends TestCase
{
    /**
     * Value label records (type 3) may only be used for strings up to 8 bytes,
     * the labels of wider strings must be written to the long string value labels record (subtype 21).
     */
    public function testStringValueLabels()
    {
        $data = [
            'header' => [
                'prodName' => '@(#) IBM SPSS STATISTICS',
                'layoutCode' => 2,
                'creationDate' => '08 May 19',
                'creationTime' => '12:22:16',
            ],
            'variables' => [
                [
                    'name' => 'short',
                    'label' => 'short label',
                    'format' => Variable::FORMAT_TYPE_A,
                    'width' => 8,
                    'values' => [
                        'a1' => 'Short Zürich',
                    ],
                    'data' => ['a1'],
                ],
                [
                    'name' => 'wide',
                    'label' => 'wide label',
                    'format' => Variable::FORMAT_TYPE_A,
                    'width' => 28,
                    'values' => [
                        'b1' => 'Wide Zürich',
                        'b2' => 'Wide Genève',
                    ],
                    'data' => ['b2'],
                ],
            ],
        ];

        $writer = new Writer($data);

        $buffer = $writer->getBuffer();
        $buffer->rewind();

        $reader = Reader::fromString($buffer->getStream())->read();

        // Only the short string uses a value label record
        $this->assertCount(1, $reader->valueLabels);
        $this->assertEquals('Short Zürich', $reader->valueLabels[0]->labels[0]['label']);

        // The wide string uses the long string value labels record
        $longStringValueLabels = $reader->info[LongStringValueLabels::SUBTYPE]->data;
        $this->assertEquals(['wide'], array_keys($longStringValueLabels));
        $this->assertEquals($data['variables'][1]['values'], $longStringValueLabels['wide']['values']);

        // Both are read back to the variables
        $this->assertEquals($data['variables'][0]['values'], $reader->variables[0]->values);
        $this->assertEquals($data['variables'][1]['values'], $reader->variables[1]->values);
    }
}
