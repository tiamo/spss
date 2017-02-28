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
    public $highest = 0;

    /**
     * @var double
     */
    public $lowest = 0;

    /**
     * @var int Always set to 4.
     */
    protected $dataSize = 4;

    /**
     * @var int Always set to 8.
     */
    protected $dataCount = 8;

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