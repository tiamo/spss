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
     * Number of bytes per segment by which the amount of space for very long string variables is allocated
     */
    const EFFECTIVE_VLS_CHUNK = 252;

    /**
     * @var int Variable width.
     * Set to 0 for a numeric variable.
     * For a short string variable or the first part of a long string variable, this is set to the width of the string.
     * For the second and subsequent parts of a long string variable, set to -1, and the remaining fields in the structure are ignored.
     */
    public $width;

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
        $this->width = $buffer->readInt();
        $hasLabel = $buffer->readInt();
        $this->missingValuesFormat = $buffer->readInt();
        $this->print = Buffer::intToBytes($buffer->readInt());
        $this->write = Buffer::intToBytes($buffer->readInt());
        $this->name = rtrim($buffer->readString(8));
        if ($hasLabel) {
            $labelLength = $buffer->readInt();
            $this->label = $buffer->readString($labelLength, 4);
        }
        if ($this->missingValuesFormat != 0) {
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
        $seg0width = self::segmentAllocWidth($this->width, 0);
        $hasLabel = !empty($this->label);

        $buffer->writeInt(self::TYPE);
        $buffer->writeInt($seg0width);
        $buffer->writeInt($hasLabel ? 1 : 0);
        $buffer->writeInt($this->missingValuesFormat);
        $buffer->writeInt(Buffer::bytesToInt($this->print));
        $buffer->writeInt(Buffer::bytesToInt($this->write));
        $buffer->writeString($this->name, 8);
        if ($hasLabel) {
            $labelLength = strlen($this->label);
            $buffer->writeInt($labelLength);
            $buffer->writeString($this->label, Buffer::roundUp($labelLength, 4));
        }
        if ($this->missingValuesFormat) {
            foreach ($this->missingValues as $val) {
                if ($this->width == 0) {
                    $buffer->writeDouble($val);
                } else {
                    $buffer->writeString($val, 8);
                }
            }
        }
        $this->writeBlank($buffer, $seg0width);
        if (self::isVeryLong($this->width)) {
            $countSegments = self::widthToSegments($this->width);
            for ($i = 1; $i < $countSegments; $i++) {
                $segWidth = self::segmentAllocWidth($this->width, $i);
                $buffer->writeInt(self::TYPE);
                $buffer->writeInt($segWidth);
                $buffer->writeInt(0);
                $buffer->writeInt(0);
                $buffer->writeInt(0);
                $buffer->writeInt(0);
                $buffer->writeString($this->name, 8); // TODO: unique name
//                $buffer->writeString(substr($this->name, 0, - strlen($i)) . $i, 8);
                $this->writeBlank($buffer, $segWidth);
            }
        }
    }

    /**
     * @param Buffer $buffer
     * @param int $width
     */
    public function writeBlank(Buffer $buffer, $width)
    {
        for ($i = 8; $i < $width; $i += 8) {
            $buffer->writeInt(self::TYPE);
            $buffer->writeInt(-1);
            $buffer->writeInt(0);
            $buffer->writeInt(0);
            $buffer->writeInt(0);
            $buffer->writeInt(0);
            $buffer->write('        ');
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


    /**
     * Returns true if WIDTH is a very long string width, false otherwise.
     * @param int $width
     * @return int
     */
    public static function isVeryLong($width)
    {
        return $width > self::REAL_VLS_CHUNK;
    }

    /**
     * Returns the number of bytes of uncompressed case data used for writing a variable of the given WIDTH to a system file.
     * All required space is included, including trailing padding and internal padding.
     * @param int $width
     * @return int
     */
    public static function widthToBytes($width)
    {
        if ($width == 0) {
            $bytes = 8;
        } elseif (!self::isVeryLong($width)) {
            $bytes = $width;
        } else {
            $chunks = $width / self::EFFECTIVE_VLS_CHUNK;
            $remainder = $width % self::EFFECTIVE_VLS_CHUNK;
            $bytes = $remainder + ($chunks + Buffer::roundUp(self::REAL_VLS_CHUNK, 8));
        }
        return Buffer::roundUp($bytes, 8);
    }

    /**
     * Returns the number of 8-byte units (octs) used to write data for a variable of the given WIDTH.
     * @param int $width
     * @return int
     */
    public static function widthToOcts($width)
    {
        return self::widthToBytes($width) / 8;
    }

    /**
     * Returns the number of "segments" used for writing case data for a variable of the given WIDTH.
     * A segment is a physical variable in the system file that represents some piece of a logical variable.
     * Only very long string variables have more than one segment.
     * @param int $width
     * @return int
     */
    public static function widthToSegments($width)
    {
        return self::isVeryLong($width) ? ceil($width / self::EFFECTIVE_VLS_CHUNK) : 1;
    }

    /**
     * Returns the width to allocate to the given SEGMENT within a variable of the given WIDTH.
     * A segment is a physical variable in the system file that represents some piece of a logical variable.
     * @param int $width
     * @param int $segment
     * @return int
     */
    public static function segmentAllocWidth($width, $segment)
    {
        return self::isVeryLong($width) ?
            ($segment < self::widthToSegments($width) - 1 ?
                self::REAL_VLS_CHUNK :
                $width - $segment * self::EFFECTIVE_VLS_CHUNK) :
            $width;
    }

    /**
     * SPSS represents a date as the number of seconds since the epoch, midnight, Oct. 14, 1582.
     * @param $timestamp
     * @param string $format
     * @return false|int
     */
    public static function date($timestamp, $format = 'Y M d')
    {
        return date($format, strtotime('1582-10-04 00:00:00') + $timestamp);
    }
}
