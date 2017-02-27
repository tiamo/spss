<?php

namespace SPSS\Sav;

use SPSS\Buffer;
use SPSS\Sav\Record\Header;

class Writer
{
    /**
     * @var Buffer
     */
    protected $buffer;

    /**
     * Writer constructor.
     */
    public function __construct()
    {
        $this->buffer = new Buffer();

        $header = new Header();
        $header->prodName = '@(#) IBM SPSS STATISTICS tiamo/spss';
        $header->creationDate = date('d M y');
        $header->creationTime = date('H:i:s');
        $header->fileLabel = 'test';
        $header->write($this->buffer);

        // TODO: other records
    }

    public function save($file)
    {
        $this->buffer->saveToFile($file);
    }
}