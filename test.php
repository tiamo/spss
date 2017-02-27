#!/usr/bin/php
<?php

require 'vendor/autoload.php';
date_default_timezone_set('UTC');

use SPSS\Buffer;
use SPSS\Sav\Reader;

// test read data
$reader = Reader::fromFile('tmp/test.sav');

exit;

$buffer = new Buffer();

$header = new \SPSS\Sav\Record\Header();
$header->prodName = '@(#) IBM SPSS STATISTICS tiamo/spss';
$header->creationDate = date('d M y');
$header->creationTime = date('H:i:s');
$header->fileLabel = 'test';
$header->write($buffer);

$buffer->saveToFile('tmp/data.sav');
