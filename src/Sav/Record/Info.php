<?php

namespace SPSS\Sav\Record;

use SPSS\Buffer;
use SPSS\Sav\Record;

abstract class Info extends Record
{
    const TYPE = 7;
    const SUBTYPE = 0;

    /**
     * @var int Size of each piece of data in the data part, in bytes
     */
    protected $dataSize = 1;

    /**
     * @var int Number of pieces of data in the data part
     */
    protected $dataCount = 0;

    /**
     * @param Buffer $buffer
     */
    public function read(Buffer $buffer)
    {
        $this->dataSize = $buffer->readInt();
        $this->dataCount = $buffer->readInt();
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        $buffer->writeInt(self::TYPE);
        $buffer->writeInt(static::SUBTYPE);
        $buffer->writeInt($this->dataSize);
        $buffer->writeInt($this->dataCount);
    }
}
