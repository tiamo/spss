<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Sav\Record\Info;

class LongStringMissingValues extends Info
{
    const SUBTYPE = 22;

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
            $count = ord($buffer->read(1));
            $this->data[$varName] = [];
            for ($i = 0; $i < $count; $i++) {
                $valueLength = $buffer->readInt();
                $value = trim($buffer->readString($valueLength));
                $this->data[$varName][] = $value;
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
        foreach ($this->data as $varName => $values) {
            $buffer->writeInt(strlen($varName));
            $buffer->writeString($varName);
            foreach ($values as $value) {
                $buffer->writeInt(strlen($value));
                $buffer->writeString($value);
            }
        }
    }
}