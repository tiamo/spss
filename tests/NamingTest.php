<?php

use SPSS\Sav\Reader;
use SPSS\Sav\Record\Info\LongVariableNames;
use SPSS\Sav\Writer;
use SPSS\Tests\TestCase;

class NamingTest extends TestCase
{
    public function illegalNameProvider()
    {
        return [
            ['#FOO', ''],
            ['$FOO', ''],
            ['.FOO', ''],
            ['FOO.', ''],
            ['FOO_', ''],
        ];
    }

    public function testReservedNames()
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
                    'name'   => 'WITH',
                    'width'  => 16,
                    'format' => 1,
                ],
                [
                    'name'   => 'OR',
                    'format' => 5,
                ],
            ],
        ];
        $writer = new Writer($data);

        $buffer = $writer->getBuffer();
        $buffer->rewind();

        $reader = Reader::fromString($buffer->getStream())->read();

        $this->assertRegExp('/^' . $data['variables'][0]['name'] . '[\w]{13}$/', $reader->info[LongVariableNames::SUBTYPE]['V00001']);
        $this->assertRegExp('/^' . $data['variables'][1]['name'] . '[\w]{13}$/', $reader->info[LongVariableNames::SUBTYPE]['V00002']);
    }


    /**
     * @dataProvider illegalNameProvider
     */
    public function testIllegalNames($name)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Variable name `%s` contains an illegal character.', $name));

        $data = [
            'header'    => [
                'prodName'     => '@(#) IBM SPSS STATISTICS',
                'layoutCode'   => 2,
                'creationDate' => date('d M y'),
                'creationTime' => date('H:i:s'),
            ],
            'variables' => [
                [
                    'name'   => $name,
                    'width'  => 16,
                    'format' => 1,
                ],
            ],
        ];

        new Writer($data);
    }
}
