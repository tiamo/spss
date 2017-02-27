<?php

namespace SPSS\Sav\Record;

use SPSS\Buffer;
use SPSS\Sav\Record;

class Variable extends Record
{
    const TYPE = 2;

    /**
     * Number of bytes really stored in each segment of a very long string variable.
     */
    const REAL_VLS_CHUNK = 255;

    /**
     * @var int Variable type code.
     * Set to 0 for a numeric variable.
     * For a short string variable or the first part of a long string variable, this is set to the width of the string.
     * For the second and subsequent parts of a long string variable, set to -1, and the remaining fields in the structure are ignored.
     */
    public $type;

    /**
     * @var int
     * If the variable has no missing values, set to 0.
     * If the variable has one, two, or three discrete missing values, set to 1, 2, or 3, respectively.
     * If the variable has a range for missing variables, set to -2;
     * if the variable has a range for missing variables plus a single discrete value, set to -3.
     * A long string variable always has the value 0 here.
     * A separate record indicates missing values for long string variables @see \SPSS\Sav\Record\Info\LongStringMissingValues
     */
    public $missingValuesFormat = 0;

    /**
     * @var array
     * Print format for this variable.
     */
    public $print = [0, 0, 0, 0];

    /**
     * @var array
     * Write format for this variable.
     */
    public $write = [0, 0, 0, 0];

    /**
     * @var string Variable name.
     * The variable name must begin with a capital letter or the at-sign (‘@’).
     * Subsequent characters may also be digits, octothorpes (‘#’), dollar signs (‘$’), underscores (‘_’), or full stops (‘.’).
     * The variable name is padded on the right with spaces.
     */
    public $name;

    /**
     * @var string
     * It has length label_len, rounded up to the nearest multiple of 32 bits.
     * The first label_len characters are the variable’s variable label.
     */
    public $label;

    /**
     * @var array
     * It has the same number of 8-byte elements as the absolute value of $missingValuesFormat.
     * Each element is interpreted as a number for numeric variables (with HIGHEST and LOWEST indicated as described in the chapter introduction).
     * For string variables of width less than 8 bytes, elements are right-padded with spaces;
     * for string variables wider than 8 bytes,
     * only the first 8 bytes of each missing value are specified, with the remainder implicitly all spaces.
     * For discrete missing values, each element represents one missing value.
     * When a range is present, the first element denotes the minimum value in the range,
     * and the second element denotes the maximum value in the range.
     * When a range plus a value are present, the third element denotes the additional discrete missing value.
     */
    public $missingValues = [];

    /**
     * @param Buffer $buffer
     */
    public function read(Buffer $buffer)
    {
        $this->type = $buffer->readInt();
        $hasLabel = $buffer->readInt();
        $this->missingValuesFormat = $buffer->readInt();
        $this->print = Buffer::intToBytes($buffer->readInt());
        $this->write = Buffer::intToBytes($buffer->readInt());
        $this->name = rtrim($buffer->readString(8));
        if ($hasLabel) {
            $labelLength = $buffer->readInt();
            $this->label = $buffer->readString($labelLength);
        }
        if ($this->missingValuesFormat) {
            for ($i = 0; $i < abs($this->missingValuesFormat); $i++) {
                $this->missingValues[] = $buffer->readDouble();
            }
        }
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        $hasLabel = !empty($this->label);
        $buffer->writeInt(self::TYPE);
        $buffer->writeInt(min($this->type, self::REAL_VLS_CHUNK));
        $buffer->writeInt($hasLabel ? 1 : 0);
        $buffer->writeInt($this->missingValuesFormat);
        $buffer->writeInt(Buffer::bytesToInt($this->print));
        $buffer->writeInt(Buffer::bytesToInt($this->write));
        $buffer->writeString($this->name, 8);
        if ($hasLabel) {
            $labelLength = Buffer::roundUp(strlen($this->label), 4);
            $buffer->writeInt($labelLength);
            $buffer->writeString($this->label, $labelLength);
        }
        if ($this->missingValuesFormat) {
            foreach ($this->missingValues as $val) {
                $buffer->writeDouble($val);
            }
        }
        $this->writeBlank($buffer, $this->type);

        // TODO: very long segments
    }

    /**
     * @param Buffer $buffer
     * @param $width
     */
    public function writeBlank(Buffer $buffer, $width)
    {
        $width = min(self::REAL_VLS_CHUNK, $width);
        for ($i = 8; $i < $width; $i += 8) {
            $buffer->writeInt(self::TYPE);
            $buffer->writeInt(-1);
            $buffer->writeInt(0);
            $buffer->writeInt(0);
            $buffer->writeInt(0);
            $buffer->writeInt(0);
            $buffer->write('00000000');
        }
    }

    /**
     * @return int
     */
    public function getPrintDecimals()
    {
        return $this->print[0];
    }

    /**
     * @return int
     */
    public function getPrintWidth()
    {
        return $this->print[1];
    }

    /**
     * @return int
     */
    public function getPrintFormat()
    {
        return $this->print[2];
    }
}
