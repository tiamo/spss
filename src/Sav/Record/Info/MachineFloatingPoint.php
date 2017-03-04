<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Sav\Record\Info;

class MachineFloatingPoint extends Info
{
    const SUBTYPE = 4;

    /**
     * @var double
     */
    public $sysmis = -1.7976931348623E+308;

    /**
     * @var double
     */
    public $highest;

    /**
     * @var double
     */
    public $lowest;

    /**
     * @var int Always set to 8.
     */
    protected $dataSize = 8;

    /**
     * @var int Always set to 3.
     */
    protected $dataCount = 3;

    /**
     * @param Buffer $buffer
     */
    public function read(Buffer $buffer)
    {
        parent::read($buffer);
        $this->sysmis = $buffer->readDouble();
        $this->highest = $buffer->readDouble();
        $this->lowest = $buffer->readDouble();
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        parent::write($buffer);
        $buffer->writeDouble($this->sysmis);
        $buffer->writeDouble($this->highest ? $this->highest : -$this->sysmis);
        $buffer->writeDouble($this->lowest ? $this->lowest : -$this->sysmis);
    }
}