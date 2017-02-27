<?php

namespace SPSS\Sav\Record;

use SPSS\Buffer;
use SPSS\Exception;
use SPSS\Sav\Record;

/**
 * The value label records documented in this section are used for numeric and short string variables only.
 * Long string variables may have value labels, but their value labels are recorded using a different record type
 * @see \SPSS\Sav\Record\Info\LongStringValueLabels
 */
class ValueLabel extends Record
{
    const TYPE = 3;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var array
     * A list of dictionary indexes of variables to which to apply the value labels
     * String variables wider than 8 bytes may not be specified in this list.
     */
    public $vars = [];

    /**
     * @param Buffer $buffer
     * @throws Exception
     */
    public function read(Buffer $buffer)
    {
        /** @var int $labelCount Number of value labels present in this record. */
        $labelCount = $buffer->readInt();
        for ($i = 0; $i < $labelCount; $i++) {
            $value = $buffer->readString(8);
            $labelLength = ord($buffer->read(1));
            $label = $buffer->readString(Buffer::roundUp($labelLength + 1, 8) - 1);
            $this->data[$value] = $label;
        }

        // The value label variables record is always immediately followed after a value label record.
        $recType = $buffer->readInt();
        if ($recType != 4) {
            throw new Exception(
                sprintf('Error reading Variable Index record: bad record type [%s]. Expecting Record Type 4.', $recType)
            );
        }

        // Number of variables that the associated value labels from the value label record are to be applied.
        $varCount = $buffer->readInt();
        for ($i = 0; $i < $varCount; $i++) {
            $this->vars[] = $buffer->readInt() -1;
        }
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        $buffer->writeInt(self::TYPE);
        $buffer->writeInt(count($this->data));
        foreach ($this->data as $value => $label) {
            $labelLength = min(strlen($label), 255);
            $buffer->writeString($value, 8);
            $buffer->write(chr($labelLength));
            $buffer->writeString($label, Buffer::roundUp($labelLength + 1, 8) - 1);
        }
        $buffer->writeInt(4);
        $buffer->writeInt(count($this->vars));
        foreach ($this->vars as $index) {
            $buffer->writeInt($index);
        }
    }
}
