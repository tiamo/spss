<?php

namespace SPSS\Sav\Record;

use SPSS\Buffer;
use SPSS\Sav\Record;

class Info extends Record
{
    const TYPE = 7;

    /**
     * @var int
     */
    public $dataSize = 1;

    /**
     * @var int
     */
    public $dataCount = 0;

    /**
     * @param Buffer $buffer
     */
    public function read(Buffer $buffer)
    {
        // TODO: Implement read() method.
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        // TODO: Implement write() method.
    }
}
