<?php

namespace SPSS\Sav;

use SPSS\Buffer;

abstract class Record implements RecordInterface
{
    /**
     * Record constructor.
     *
     * @param  array  $data
     */
    public function __construct($data = array())
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @param  array  $data
     *
     * @return static
     */
    public static function fill(Buffer $buffer, $data = array())
    {
        $record = new static($data);
        $record->read($buffer);

        return $record;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function create($data = array())
    {
        return new static($data);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array();
    }
}
