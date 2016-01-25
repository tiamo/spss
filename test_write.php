<?php

require 'SPSSWriter.php';

$test = new TestSPSSWriter();
$test->generate(50);
$test->run();

class TestSPSSWriter
{
	public $countVariables;

	protected $spss;

	/**
	 * {@inheritdoc}
	 */
	public function __construct()
	{
		$this->spss = new SPSSWriter();
		// $this->spss->header = 'My App Name';
		$this->spss->numberOfCases = 12;
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate($count)
	{
		for($i=0;$i<$count;$i++) {
			$this->spss->variables[] = rand(0,1) ? $this->generateNumbericVar() : $this->generateStringVar();
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function generateNumbericVar()
	{
		$var = new SPSSVariable();
		$var->typeCode = 0;
		$var->columns = 8;
		$var->shortName = 'num'. $i; // short name (maximum 8 bytes)
		$var->name = 'Numeric'; // full variable name
		$var->label = 'Select number';
		$var->missingValueFormatCode = 0;
		$var->missingValues = array();
		$var->printFormatCode = SPSSWriter::bytesToInt(array(0, 1, 5, 0)); // 4 bytes (decimals, width, type, zero)
		$var->writeFormatCode = SPSSWriter::bytesToInt(array(0, 1, 5, 0)); // 4 bytes (decimals, width, type, zero)
		$var->valueLabels = array(
			1 => 'Number 1',
			2 => 'Number 2',
			3 => 'Number 3',
		);
		$var->measure = rand(1,3); // nominal
		$var->alignment = rand(0,2);
		for($n=0;$n<$this->spss->numberOfCases;$n++) {
			$var->data[] = rand(0, 1000);
		}
		return $var;
	}

	/**
	 * {@inheritdoc}
	 */
	public function generateStringVar()
	{
		$var = new SPSSVariable();
		$var->typeCode = 1; // 0 - numeric, > 0 - string
		$var->columns = SPSSWriter::REAL_VLS_CHUNK * 2;
		$var->shortName = 'str'.$i; // short name (maximum 8 bytes)
		$var->name = 'String #'.$i; // full variable name
		$var->label = 'How are you ?';
		$var->missingValueFormatCode = 0;
		$var->missingValues = array();
		$var->printFormatCode = SPSSWriter::bytesToInt(array(0, $var->columns, $var->typeCode, 0)); // 4 bytes (decimals, width, type, zero)
		$var->writeFormatCode = SPSSWriter::bytesToInt(array(0, $var->columns, $var->typeCode, 0)); // 4 bytes (decimals, width, type, zero)
		$var->measure = 1; // nominal
		$var->alignment = rand(0,2);
		for ($n=0;$n<$this->spss->numberOfCases;$n++) {
			$data = array(
				'Wonderful',
				'Good',
				'Not good',
				'Bad'
			);
			$var->data[] = $data[rand(0, count($data)-1)];
		}
		return $var;
	}

	/**
	 * {@inheritdoc}
	 */
	public function run()
	{
		$this->spss->save('data.sav');
	}
}

