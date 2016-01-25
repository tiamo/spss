<?php

$charactersToRead = 255;
echo floor( (($charactersToRead - 1) / 8) + 1 );
exit;

$segments = ceil($width/255);
for ($i=1;$i<$segments;$i++) {
	$seg_width = min(255, $width - ($i * 255));
	// echo $i . '<br>';
	echo $seg_width . '<br>';
}

exit;

define('REAL_VLS_CHUNK', 255);
define('EFFECTIVE_VLS_CHUNK', 252);

function round_up2 ($value, $places=0) {
  if ($places < 0) { $places = 0; }
  $mult = pow(10, $places);
  return ceil($value * $mult) / $mult;
}

function ROUND_UP($x, $y) {
	return ((($x) + (($y) - 1)) / ($y) * ($y));
}
// function ROUND_UP($x, $y) {
	// return ceil($x/$y) * $y;
// }


echo 1 - (1%8);
exit;

$width = 2;

$chunks = $width / EFFECTIVE_VLS_CHUNK;
$remainder = $width % EFFECTIVE_VLS_CHUNK;
$bytes = $remainder + ($chunks * round_up (REAL_VLS_CHUNK, 8));

echo $bytes;
exit;
