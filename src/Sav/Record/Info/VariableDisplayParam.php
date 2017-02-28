<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Exception;
use SPSS\Sav\Record\Info;

class VariableDisplayParam extends Info
{
    const SUBTYPE = 11;

    const ALIGN_LEFT = 0;
    const ALIGN_RIGHT = 1;
    const ALIGN_CENTER = 2;

    const MEASURE_NOM = 1;
    const MEASURE_ORD = 2;
    const MEASURE_CON = 3;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var int
     */
    protected $dataSize = 4;

    /**
     * @param Buffer $buffer
     * @throws Exception
     */
    public function read(Buffer $buffer)
    {
        parent::read($buffer);
        if ($this->dataSize != 4) {
            throw new Exception(
                sprintf('Error reading record type 7 subtype 11: bad data element length [%s]. Expecting 4.', $this->dataSize)
            );
        }
        if (($this->dataCount % 3) != 0) {
            throw new Exception(
                sprintf('Error reading record type 7 subtype 11: number of data elements [%s] is not a multiple of 3.', $this->dataCount)
            );
        }
        $itemCount = $this->dataCount / 3;
        for ($i = 0; $i < $itemCount; $i++) {
            $this->data[] = [
                $buffer->readInt(), // The measurement type of the variable
                $buffer->readInt(), // The width of the display column for the variable in characters.
                $buffer->readInt(), // The alignment of the variable
            ];
        }
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        $this->dataCount = $this->data * 3;
        parent::write($buffer);
        foreach ($this->data as $item) {
            $buffer->writeInt($item[0]);
            $buffer->writeInt($item[1]);
            $buffer->writeInt($item[2]);
        }
    }
}