<?php
header('Content-type:text/html;charset=cp1251');

// make conf
$filename = 'data';
$numberOfCases = 5;
$strVarCount = 10;
$numVarCount = 10;

require 'SPSSWriter.php';
$SPSS = new SPSSWriter();
// $SPSS->writer = 'My App Name';
$SPSS->numberOfCases = $numberOfCases;
// $SPSS->documents = array('doc1','doc2');

// NUMERIC variables
for($i=0;$i<$numVarCount;$i++) {
	$var = new SPSSVariable();
	$var->typeCode = 0;
	$var->shortName = 'НУМ'. $i; // short name (maximum 8 bytes)
	$var->name = 'Числоваяпеременная'.$i; // full variable name
	$var->label = 'Выберите вариант ответа #'. $i;
	$var->missingValueFormatCode = 0;
	$var->missingValues = array();
	$var->printFormatCode = 327936; // 4 bytes (decimals, width, type, zero)
	$var->writeFormatCode = 327936; // 4 bytes (decimals, width, type, zero)
	$var->valueLabels = array(
		1 => 'label aaa',
		2 => 'label bbb',
		3 => 'label ccc',
	);
	$var->measure = rand(1,3); // nominal
	$var->width = rand(5,100);
	$var->alignment = rand(0,2);
	for($n=0;$n<$SPSS->numberOfCases;$n++) {
		$var->data[] = rand(0, 1000);
	}
	$SPSS->variables[] = $var;
}

// STRING variables
for($i=0;$i<$strVarCount;$i++) {
	$var = new SPSSVariable();
	$var->typeCode = 10;//rand(100,255); // rand(0, 200); // 0 - numeric, > 0 - string
	$var->shortName = 'ТЕСТ'.$i;//'V2'.$i; // short name (maximum 8 bytes)
	$var->name = 'тесттест'.$i;// 'Строковая переменная №'.$i; // full variable name
	$var->label = 'Как дела? #'.$i;
	$var->missingValueFormatCode = 0;
	$var->missingValues = array();
	$var->printFormatCode = 130816; // 4 bytes (decimals, width, type, zero)
	$var->writeFormatCode = 130816; // 4 bytes (decimals, width, type, zero)
	$var->measure = 1; // nominal
	$var->width = rand(5,100);
	$var->alignment = rand(0,2);
	for($n=0;$n<$SPSS->numberOfCases;$n++) {
		$data = array('Хорошо', 'Плохо', 'Отлично', 'Не очень');
		$var->data[] = $data[rand(0, count($data)-1)];
	}
	$SPSS->variables[] = $var;
}

// MAKE
$SPSS->make();
$SPSS->save($filename.'.sav');
