<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Sav\Record\Info;

class VeryLongString extends Info
{
    const SUBTYPE = 14;
    const DELIMITER = "\t";

    /**
     * @var array
     */
    public $data = [];

    /**
     * @param Buffer $buffer
     */
    public function read(Buffer $buffer)
    {
        parent::read($buffer);
        $data = rtrim($buffer->readString($this->dataSize * $this->dataCount));
        foreach (explode(self::DELIMITER, $data) as $item) {
            list($key, $value) = explode('=', $item);
            $this->data[$key] = trim($value);
        }
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        $data = '';
        foreach ($this->data as $key => $value) {
            $data .= sprintf('%s=%s', $key, $value) . self::DELIMITER;
        }
        $this->dataCount = strlen($data);
        parent::write($buffer);
        $buffer->writeString($data);
    }
}