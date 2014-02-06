<?php

require 'SPSS.php';
$data = SPSS::parse('data.sav');

print_r($data);
