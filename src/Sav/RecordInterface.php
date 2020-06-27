<?php

namespace SPSS\Sav;

use SPSS\Buffer;

Interface RecordInterface
{
    /**
     * @var int Record type code
     */
    const TYPE = 0;

    /**
     * @param  Buffer  $buffer
     * @return void
     */
    public function read(Buffer $buffer);

    /**
     * @param  Buffer  $buffer
     * @return void
     */
    public function write(Buffer $buffer);
}
