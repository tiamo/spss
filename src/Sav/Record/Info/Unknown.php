<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Sav\Record\Info;

class Unknown extends Info
{
    /**
     * @var string
     */
    public $data;

    /**
     * @param Buffer $buffer
     */
    public function read(Buffer $buffer)
    {
        parent::read($buffer);
        $this->data = $buffer->readString($this->dataSize * $this->dataCount);
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        $this->dataCount = strlen($this->data);
        parent::write($buffer);
        $buffer->writeString($this->data);
    }
}