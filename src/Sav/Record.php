<?php

namespace SPSS\Sav;

use SPSS\Buffer;

abstract class Record
{
    /**
     * @var int Record type code
     */
    const TYPE = 0;

    /**
     * Record constructor.
     * @param array $data
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @param Buffer $buffer
     * @return self|static
     */
    public static function fill(Buffer $buffer)
    {
        $record = new static();
        $record->read($buffer);
        return $record;
    }

    /**
     * @param Buffer $buffer
     * @return void
     */
    abstract public function read(Buffer $buffer);

    /**
     * @param Buffer $buffer
     * @return void
     */
    abstract public function write(Buffer $buffer);
}