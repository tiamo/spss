<?php
/**
 * SPSS Php Writer
 * 
 * @package fom.spss
 * @author vk.tiamo@gmail.com
 */

require_once 'SPSSVariable.php';
require_once 'SPSSAbstract.php';

class SPSSWriter extends SPSSAbstract
{
	const FILE_SIGNATURE = '$FL2';

	/**
	 * @var string File header (max 60 symbols)
	 */
	public $header = '@(#) SPSS DATA FILE';

	/**
	 * @var string File label (max 64 symbols)
	 */
	public $fileLabel = '';

	/**
	 * @var array Release version (major, minor, special)
	 */
	public $release = array(1,1,0);

	/**
	 * @var string Charset
	 */
	public $charset = 'windows-1251';

	/**
	 * @var integer Compression Bias (0 - uncompess)
	 */
	public $compression = 0;

	/**
	 * @var integer Number of cases
	 */
	public $numberOfCases = -1;

	/**
	 * @var integer System machine code
	 */
	public $machineCode = 720;

	/**
	 * @var integer System missing value
	 */
	public $sysmis = -1.7976931348623E+308;

	/**
	 * @var array Variables list
	 */
	public $variables = array();

	/**
	 * @var array Documents list
	 */
	public $documents = array();

	/**
	 * @var integer Creation timestamp
	 */
	public $timestamp;

	/**
	 * {@inheritdoc}
	 * @return string bytes
	 */
	public function make()
	{
		if (!$this->variables) {
			throw new SPSSException('Variables data is empty');
		}
		$bytes = $this->_header();
		foreach($this->variables as $var) {
			$bytes .= $this->_variableRecord($var);
		}
		foreach($this->variables as $var) {
			$bytes .= $this->_valueLabelRecord($var);
		}
		$bytes .= $this->_documentsRecord();
		$bytes .= $this->_additionalRecord();
		$bytes .= $this->_dataRecord();
		return $bytes;
	}

	/**
	 * Save bytes to file
	 * 
	 * @param string $filename
	 * @return void
	 */
	public function save($filename=null)
	{
		if (!$filename) {
			$filename = time() . '.sav';
		}
		if ($fp = fopen($filename, 'wb+')) {
			fwrite($fp, $this->make());
			fclose($fp);
		}
	}

	/**
	 * Make header information
	 * 
	 * @return string bytes
	 */
	private function _header()
	{
		if (!$this->timestamp) {
			$this->timestamp = time();
		}
		$bytes = '';
		$bytes .= pack('a4', self::FILE_SIGNATURE);
		$bytes .= pack('A60', substr($this->header, 0, 60)); // identification
		$bytes .= pack('i', 2); // layoutCode
		$bytes .= pack('i', count($this->variables)); // numberOfVariables
		$bytes .= pack('i', (int) !empty($this->compression)); // compressionSwitch
		$bytes .= pack('i', 0); // caseWeightVariable
		$bytes .= pack('i', $this->numberOfCases); // numberOfCases
		$bytes .= pack('d', $this->compression); // compressionBias
		$bytes .= pack('a9', date('d M y', $this->timestamp)); // creation date
		$bytes .= pack('a8', date('H:i:s', $this->timestamp)); // creation time
		$bytes .= pack('A64', substr($this->encodeStr($this->fileLabel), 0, 64)); // file label
		$bytes .= pack('x3'); // padding
		return $bytes;
	}

	/**
	 * SPSS Record Type 2 - Variable
	 * 
	 * @param SPSSVariable $var
	 * @return string
	 */
	private function _variableRecord(SPSSVariable $var)
	{
		$shortName = substr($this->encodeStr($var->shortName), 0, 8);
		$width = $var->typeCode;
		$isLabeled = !empty($var->label);
		$isVeryLong = self::isVeryLong($width);
		
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_VARIABLE);
		$bytes .= pack('i', $isVeryLong ? self::REAL_VLS_CHUNK : $width); // width
		$bytes .= pack('i', (int) $isLabeled); // has label?
		$bytes .= pack('i', $var->missingValueFormatCode); // missingValueFormatCode (-3 to 3)
		$bytes .= pack('i', $var->printFormatCode); // printFormatCode
		$bytes .= pack('i', $var->writeFormatCode); // writeFormatCode
		$bytes .= pack('A8', $shortName); // shortName

		if ($isLabeled) {
			$label = $this->encodeStr($var->label);
			$labelLength = strlen($label);
			$bytes .= pack('i', $labelLength); // label length
			$bytes .= pack('A'.self::roundUp($labelLength, 4), $label); // label
		}

		if ($var->missingValues) {
			foreach($var->missingValues as $key => $val) {
				$bytes .= pack('d', $val);
			}
		}

		if ($width > 0) {
			$bytes .= $this->_variableContinuationRecord($width);
		}
		
		// Write additional segments for very long string variables.
		if ($isVeryLong) {
			$segmentsCount = ceil($width/self::REAL_VLS_CHUNK);
			for ($i=1;$i<$segmentsCount;$i++) {
				$segmentWidth = min($width - ($i * self::REAL_VLS_CHUNK), self::REAL_VLS_CHUNK);
				$segmentFormat = self::toInt(array(SPSSVariable::FORMAT_TYPE_A, max($segmentWidth, 1), 0, 0));
				$bytes .= pack('i', self::RECORD_TYPE_VARIABLE);
				$bytes .= pack('i', $segmentWidth);
				$bytes .= pack('i', 0);
				$bytes .= pack('i', 0);
				$bytes .= pack('i', $segmentFormat);
				$bytes .= pack('i', $segmentFormat);
				$bytes .= pack('A8', substr($shortName, 0, -strlen($i)) . $i);
				$bytes .= $this->_variableContinuationRecord($segmentWidth);
			}
		}
		
		return $bytes;
	}

	/**
	 * @param integer $width
	 * @return string
	 */
	private function _variableContinuationRecord($width)
	{
		$bytes = '';
		for ($i = 8; $i < $width; $i += 8) {
			$bytes .= pack('i6', 2, -1, 0, 0, 0, 0);
			$bytes .= '00000000';
		}
		return $bytes;
	}

	/**
	 * SPSS Record Type 3|4 - Value labels | Indexes
	 * 
	 * @param SPSSVariable $var
	 * @return string
	 */
	private function _valueLabelRecord(SPSSVariable $var)
	{
		if (!$var->valueLabels) {
			return;
		}
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_VALUE_LABELS); 
		$bytes .= pack('i', count($var->valueLabels)); // number of labels
		foreach($var->valueLabels as $key => $val) {
			$label = $this->encodeStr($val);
			$labelLength = strlen($label);
			if ($labelLength>255) {
				$labelLength = 255;
			}
			$skipBytes = 0;
			if (($labelLength+1) % 8){
				$skipBytes = 8 - (($labelLength+1) % 8);
			}
			$bytes .= pack('d', $key);
			$bytes .= chr($labelLength);
			$bytes .= pack('A'.($labelLength+$skipBytes), $label);
		}
		// record type 4 (value labels index)
		$bytes .= pack('i', self::RECORD_TYPE_VALUE_LABELS_INDEX);
		$bytes .= pack('i', 1);
		$bytes .= pack('i', $this->getVarIndex($var));
		return $bytes;
	}

	/**
	 * SPSS Record Type 6 - Documents
	 * 
	 * @return string bytes
	 */
	private function _documentsRecord()
	{
		if (!$this->documents) {
			return;
		}
		$bytes = pack('i', self::RECORD_TYPE_DOCUMENTS);
		$bytes .= pack('i', count($this->documents));
		foreach($this->documents as $line) {
			$bytes .= pack('A80', $this->encodeStr($line)); // document line 80 bytes max
		}
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 - Additional information
	 * 
	 * @return string bytes
	 */
	private function _additionalRecord()
	{
		$bytes = $this->_additional7_3(); // source system characteristics
		$bytes .= $this->_additional7_4(); // machine specific "float" type information.
		// $bytes .= $this->_additional7_5(); // variable sets
		$bytes .= $this->_additional7_11(); // variable params
		$bytes .= $this->_additional7_13(); // extended names
		$bytes .= $this->_additional7_14(); // extended names
		$bytes .= $this->_additional7_16(); // number of cases
		$bytes .= $this->_additional7_20(); // charset
		return $bytes;
	}
	
	/**
	 * SPSS Record Type 7 Subtype 3 - Source system characteristics
	 * 
	 * @return string bytes
	 */
	private function _additional7_3()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 3);
		$bytes .= pack('i', 4);
		$bytes .= pack('i', 8);
		$bytes .= pack('i', $this->release[0]); // releaseMajor
		$bytes .= pack('i', $this->release[1]); // releaseMinor
		$bytes .= pack('i', $this->release[2]); // releaseSpecial
		$bytes .= pack('i', $this->machineCode); // machineCode
		/**
		  if (FLOAT_NATIVE_64_BIT == FLOAT_IEEE_DOUBLE_LE || FLOAT_NATIVE_64_BIT == FLOAT_IEEE_DOUBLE_BE)
			float_format = 1;
		  else if (FLOAT_NATIVE_64_BIT == FLOAT_Z_LONG)
			float_format = 2;
		  else if (FLOAT_NATIVE_64_BIT == FLOAT_VAX_D)
			float_format = 3;
		*/
		$bytes .= pack('i', 1); // floatRepresentation (1,2,3)
		$bytes .= pack('i', 1); // compression code
		$bytes .= pack('i', 2); // endianCode (Little-endian)
		// $bytes .= pack('i', 1251); // characterRepresentation (7-bit ASCII)
		/*
		Default to "7-bit ASCII" if the codepage number is unknown,
		because many files use this codepage number regardless
		of their actual encoding.
		*/
		$bytes .= pack('i', 2);
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 Subtype 4 - Release and machine specific "float" type information
	 * Added in SPSS release 4.0
	 * 
	 * @return string bytes
	 */
	private function _additional7_4()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 4); // Record subtype.
		$bytes .= pack('i', 8); // Data item (flt64) size.
		$bytes .= pack('i', 3); // Number of data items.
		$bytes .= pack('d', $this->sysmis); // System-missing value.
		$bytes .= pack('d', -$this->sysmis); // Value used for HIGHEST in missing values.
		$bytes .= pack('d', $this->sysmis); // Value used for LOWEST in missing values.
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 Subtype 5 -  Variable sets information
	 * 
	 * @return string bytes
	 */
	private function _additional7_5()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 5);
		$bytes .= pack('i', 1);
		$bytes .= pack('i', 0);
		$bytes .= pack('a', '');
		return $bytes;
	}
	
	/**
	 * SPSS Record Type 7 Subtype 11 - Variable display parameters
	 * 
	 * @return string bytes
	 */
	private function _additional7_11()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 11);
		$bytes .= pack('i', 4); // data element length
		$bytes .= pack('i', count($this->variables) * 3); // number of data elements
		foreach($this->variables as $var) {
			$bytes .= pack('i', $var->measure);
			$bytes .= pack('i', $var->columns);
			$bytes .= pack('i', $var->alignment);
		}
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 Subtype 13 - Long variable names
	 * 
	 * @return string bytes
	 */
	private function _additional7_13()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 13);
		$bytes .= pack('i', 1); // data element length
		$data = array();
		foreach($this->variables as $var) {
			$data[] = $var->shortName.'='.(!empty($var->name) ? $var->name : $var->shortName);
		}
		$data = implode("\t", $data);
		$datalen = strlen($data);
		$bytes .= pack('i', $datalen); // number of data elements
		$bytes .= pack('a'.$datalen, $this->encodeStr($data));
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 Subtype 14 - Long variable value
	 * 
	 * @return string bytes
	 */
	private function _additional7_14()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 14);
		$bytes .= pack('i', 1); // data element length
		$data = array();
		foreach($this->variables as $var) {
			
			// @TODO
			
			// $width = $var->getWidth();
			// $segmentsCount = ceil($width/self::REAL_VLS_CHUNK);
			// for ($i=1;$i<$segmentsCount;$i++) {
				// $segmentWidth = min($width - ($i * self::REAL_VLS_CHUNK), self::REAL_VLS_CHUNK);
				// $shortName = substr($var->shortName, 0, -strlen($i)) . $i;
				// $data[] = $shortName.'='.substr();
			// }
			
		}
		$data = implode("\t", $data);
		$datalen = strlen($data);
		$bytes .= pack('i', $datalen); // number of data elements
		$bytes .= pack('a'.$datalen, $this->encodeStr($data));
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 Subtype 16 - Number of cases
	 * 
	 * @return string bytes
	 */
	private function _additional7_16()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 16);
		$bytes .= pack('i', 8); // data element length
		$bytes .= pack('i', 2); // number of data elements
		$bytes .= pack('i', 1); // byte order
		$bytes .= pack('i', 0); 
		$bytes .= pack('d', 0);
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 Subtype 20 - Charset
	 * 
	 * @return string bytes
	 */
	private function _additional7_20()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 20);
		$bytes .= pack('i', 1); // data element length
		$bytes .= pack('i', strlen($this->charset)); // number of data elements
		$bytes .= pack('a*', $this->charset);
		return $bytes;
	}

	/**
	 * SPSS Record Type 999 - Data record
	 * 
	 * @return string bytes
	 */
	private function _dataRecord()
	{
		$bytes = pack('i2', self::RECORD_TYPE_FINAL, 0);
		for($i=0;$i<$this->numberOfCases;$i++) {
			foreach($this->variables as $var) {
				$type = $var->getType();
				$value = isset($var->data[$i]) ? $var->data[$i] : null;
				if ($this->compression) {
					// @todo
				}
				else {
					if ($type==SPSSVariable::TYPE_NUMERIC) {
						$bytes .= pack('d', $value);
					}
					else {
						$width = $var->getWidth();
						$bytes .= pack('A'.$width, $value);
					}
				}
			}
		}
		return $bytes;
	}

	/**
	 * Get variable index
	 * 
	 * @param SPSSVariable $var
	 * @return integer
	 */
	public function getVarIndex($var)
	{
		static $index;
		if ($var->typeCode>0) {
			$index += ceil($var->getWidth()/8);
		} else {
			$index++;
		}
		return $index;
	}

	/**
	 * Fix string charset
	 * 
	 * @params string $str
	 * @return string
	 */
	private function encodeStr($str)
	{
		if ($this->charset !='utf-8') {
			$str = iconv('utf-8', $this->charset, $str);
		}
		return $str;
	}
	
}
