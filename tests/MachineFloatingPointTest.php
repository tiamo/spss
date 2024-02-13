<?php

namespace SPSS\Tests;

use SPSS\Buffer;
use SPSS\Sav\Record\Info\MachineFloatingPoint;

class MachineFloatingPointTest extends TestCase
{
    public function provider()
    {
        return [
            [
                [
                    'sysmis'  => -1,
                    'highest' => 5,
                    'lowest'  => -10,
                ],
                [
                    'sysmis'  => -1,
                    'highest' => 5,
                    'lowest'  => -10,
                ],
            ],
            [
                [],
                // -1.7976931348623E+308 php min double -PHP_FLOAT_MAX
                //  1.7976931348623E+308 php max double PHP_FLOAT_MAX
                [
                    'sysmis'  => -PHP_FLOAT_MAX,
                    'highest' =>  PHP_FLOAT_MAX,
                    'lowest'  => -PHP_FLOAT_MAX,
                ],
            ],
        ];
    }

    /**
     * @dataProvider provider
     * @param  array  $attributes
     * @param  array  $expected
     */
    public function testWriteRead(array $attributes, array $expected)
    {
        $subject = new MachineFloatingPoint();
        foreach ($attributes as $key => $value) {
            $subject->{$key} = $value;
        }
        $buffer = Buffer::factory('', ['memory' => true]);
        $this->assertEquals(0, $buffer->position());
        $subject->write($buffer);
        $buffer->rewind();
        $buffer->skip(8);
        $read = MachineFloatingPoint::fill($buffer);
        $this->assertEquals(40, $buffer->position());
        foreach ($expected as $key => $value) {
            $msg      = "Wrong value received for '$key', expected '$value', got '{$read->{$key}}'";
            $this->assertEquals($value, $read->{$key}, $msg);
        }
    }
}
