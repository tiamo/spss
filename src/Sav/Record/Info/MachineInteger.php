<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Sav\Record\Info;

class MachineInteger extends Info
{
    const SUBTYPE = 3;

    /**
     * @var array [Major, Minor, Revision]
     */
    public $version = [1, 0, 0];

    /**
     * @var int Machine code.
     */
    public $machineCode = 0;

    /**
     * @var int Floating point representation code.
     * For IEEE 754 systems this is 1.
     * IBM 370 sets this to 2,
     * and DEC VAX E to 3.
     */
    public $floatingPointRep = 1;

    /**
     * @var int Compression code.
     * Always set to 1, regardless of whether or how the file is compressed.
     */
    public $compressionCode = 1;

    /**
     * @var int Machine endianness.
     * 1 indicates big-endian,
     * 2 indicates little-endian.
     */
    public $endianness = 1;

    /**
     * @var int Character code.
     * The following values have been actually observed in system files:
     */
    public $characterCode = 1;

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
        $this->version = [$buffer->readInt(), $buffer->readInt(), $buffer->readInt()];
        $this->machineCode = $buffer->readInt();
        $this->floatingPointRep = $buffer->readInt();
        $this->compressionCode = $buffer->readInt();
        $this->endianness = $buffer->readInt();
        $this->characterCode = $buffer->readInt();
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        parent::write($buffer);
        $buffer->writeInt($this->version[0]);
        $buffer->writeInt($this->version[1]);
        $buffer->writeInt($this->version[2]);
        $buffer->writeInt($this->machineCode);
        $buffer->writeInt($this->floatingPointRep);
        $buffer->writeInt($this->compressionCode);
        $buffer->writeInt($this->endianness);
        $buffer->writeInt($this->characterCode);
    }
}