<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Exception;
use SPSS\Sav\Record\Info;

/**
 * The data file and variable attributes records represent
 * custom attributes for the system file or for individual variables in the system file
 */
class VariableAttributes extends Unknown
{
    const SUBTYPE = 17;
}