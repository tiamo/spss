<?php

namespace SPSS\Sav;

class Variable
{
    public $name;

    public $width = 0;

    public $decimals = 0;

    public $format = 0;

    public $label;

    public $values = [];

    public $missing = [];

    public $columns = 0;

    public $align = 0;

    public $measure = 0;

    public $role;

    public $data = [];

    /**
     * Variable constructor.
     * @param array $data
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}