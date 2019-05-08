<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Sav\Record\Info;

class CharacterEncoding extends Info
{
    const SUBTYPE = 20;

    /**
     * @var string
     */
    public $value;
    
    /**
     * Record constructor.
     *
     * @param array $data
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param Buffer $buffer
     */
    public function read(Buffer $buffer)
    {
        parent::read($buffer);
        $this->value = $buffer->readString($this->dataSize * $this->dataCount);
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        $this->dataCount = strlen($this->value);
        parent::write($buffer);
        $buffer->writeString($this->value);
    }
}
