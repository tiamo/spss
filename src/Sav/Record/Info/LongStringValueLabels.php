<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Sav\Record\Info;

class LongStringValueLabels extends Info
{
    const SUBTYPE = 21;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @param Buffer $buffer
     */
    public function read(Buffer $buffer)
    {
        parent::read($buffer);
        $buffer = $buffer->allocate($this->dataCount * $this->dataSize);
        while ($varNameLength = $buffer->readInt()) {
            $varName = trim($buffer->readString($varNameLength));
            $varWidth = $buffer->readInt(); // The width of the variable, in bytes, which will be between 9 and 32767
            $lebelCount = $buffer->readInt();
            $this->data[$varName] = [];
            for ($i = 0; $i < $lebelCount; $i++) {
                $valueLength = $buffer->readInt();
                $value = trim($buffer->readString($valueLength));
                $lebelLength = $buffer->readInt();
                $label = trim($buffer->readString($lebelLength));
                $this->data[$varName][$value] = $label;
            }
        }
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        $this->dataCount = 0; // TODO:
        parent::write($buffer);
        foreach ($this->data as $varName => $labels) {
            $buffer->writeInt(strlen($varName));
            $buffer->writeString($varName);
            $buffer->writeInt(0); // TODO: get real var width by name
            $buffer->writeInt(count($labels));
            foreach ($labels as $value => $label) {
                $buffer->writeInt(strlen($value));
                $buffer->writeString($value);
                $buffer->writeInt(strlen($label));
                $buffer->writeString($label);
            }
        }
    }
}