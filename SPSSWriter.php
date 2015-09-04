<?php
/**
 * SPSS Php Writer
 * 
 * @package fom.spss
 * @author vk.tiamo@gmail.com
 */

require_once 'SPSSVariable.php';

class SPSSException extends Exception {}

class SPSSWriter
{
	const FILE_SIGNATURE = '$FL2';
	const RECORD_TYPE_VARIABLE = 2;
	const RECORD_TYPE_VALUE_LABELS = 3;
	const RECORD_TYPE_VALUE_LABELS_INDEX = 4;
	const RECORD_TYPE_DOCUMENTS = 6;
	const RECORD_TYPE_ADDITIONAL = 7;
	const RECORD_TYPE_FINAL = 999;
	
	// public $writer = '@(#) SPSS DATA FILE MS Windows 17.0.0';
	public $writer = '@(#) FOM SPSS 1.0.0'; // 60 bytes max
	public $fileLabel = ''; // 64 bytes max
	public $release = array(1,0,0); // 64 bytes max
	public $charset = 'windows-1251';
	public $compression = 0; // compression bias
	public $numberOfCases = 1;
	public $machineCode = 720;
	public $sysmis = -0x1fffffffffffff * 2.0**971;
	// public $sysmis = -1.79769313486E+308; // sysmis
	public $variables = array();
	public $documents = array();
	
	private $bytes = ''; // bytes raw
	
	public function make()
	{
		if (!$this->variables) {
			throw new SPSSException('Variables data is empty');
		}
		
		// header
		$this->bytes .= $this->_header();
		
		// variables record
		foreach($this->variables as $var) {
			$this->bytes .= $this->_variableRecord($var);
		}
		
		// value labels record
		foreach($this->variables as $var) {
			$this->bytes .= $this->_valueLabelRecord($var);
		}
		
		// documents record
		$this->bytes .= $this->_documentsRecord();
		
		// additional record
		$this->bytes .= $this->_additionalRecord();
		
		// final record
		$this->bytes .= $this->_finalRecord();
		
		// data
		$this->bytes .= $this->_data();
	}
	
	/**
	 * Save bytes to file
	 * 
	 * @param string $filename
	 * @return string
	 */
	public function save($filename=null)
	{
		if (!$filename) {
			$filename = time() . '.sav';
		}
		if ($fp = fopen($filename, 'wb+')) {
			fwrite($fp, $this->bytes);
			fclose($fp);
		}
	}
	
	/**
	 * Get bytes raw data
	 * 
	 * @return string
	 */
	public function getBytes()
	{
		return $this->bytes;
	}
	
	/**
	 * Make header information
	 * @return string
	 */
	private function _header()
	{
		$bytes = '';
		$bytes .= pack('a4', self::FILE_SIGNATURE);
		$bytes .= pack('A60', $this->writer); // identification
		$bytes .= pack('i', 2); // layoutCode
		$bytes .= pack('i', count($this->variables)); // numberOfVariables
		$bytes .= pack('i', (int) !empty($this->compression)); // compressionSwitch
		$bytes .= pack('i', 0); // caseWeightVariable
		$bytes .= pack('i', $this->numberOfCases); // numberOfCases
		$bytes .= pack('d', $this->compression); // compressionBias
		$bytes .= pack('a9', date('d M y')); // creation date
		$bytes .= pack('a8', date('H:i:s')); // creation time
		$bytes .= pack('A64', $this->fixstr($this->fileLabel)); // file label
		$bytes .= pack('x3'); // padding
		return $bytes;
	}
	
	/**
	 * Make variable (Record type 2)
	 * @return string
	 */
	private function _variableRecord(SPSSVariable $var)
	{
		$hasLabel = (int) !empty($var->label);
		
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_VARIABLE); // type variable
		$bytes .= pack('i', $var->typeCode); // typeCode
		$bytes .= pack('i', $hasLabel); // hasLabel
		$bytes .= pack('i', $var->missingValueFormatCode); // missingValueFormatCode (-3 to 3)
		$bytes .= pack('i', $var->printFormatCode); // printFormatCode
		$bytes .= pack('i', $var->writeFormatCode); // writeFormatCode
		$bytes .= pack('A8', $this->fixstr($var->shortName)); // shortName
		
		// has labels
		if ($hasLabel) {
			$label = $this->fixstr($var->label);
			$labelLength = strlen($var->label);
			// variableRecord labels are stored in chunks of 4-bytes
			// --> we need to skip unused bytes in the last chunk
			$skipBytes = 0;
			if ($labelLength % 4 != 0) {
				$skipBytes = 4 - ($labelLength % 4);
			}
			$bytes .= pack('i', $labelLength); // label length
			$bytes .= pack('A'.($labelLength+$skipBytes), $label); // label
		}
		// missing values
		if ($var->missingValues) {
			foreach($var->missingValues as $key => $val) {
				$bytes .= pack('d', $val);
			}
		}
		
		// padding
		if ($var->typeCode > 0) {
			for($k = 8; $k < $var->typeCode; $k += 8) {
				$fcode = self::toInt(array(1, 29, 1, 0));
				$bytes .= pack('i6', 2, -1, 0, 0, $fcode, $fcode);
				$bytes .= '        ';
			}
		}
		
		return $bytes;
	}
	
	/**
	 * Make value labels (Record type 3,4)
	 * @return string
	 */
	private function _valueLabelRecord(SPSSVariable $var)
	{
		$index = $this->getVarIndex($var);
		
		if (!$var->valueLabels) {
			return;
		}
		
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_VALUE_LABELS); 
		$bytes .= pack('i', count($var->valueLabels)); // number of labels
		foreach($var->valueLabels as $key => $val) {
			$label = $this->fixstr($val);
			$labelLength = strlen($val);
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
		$bytes .= pack('i', $index);
		
		return $bytes;
	}
	
	/**
	 * @param SPSSVariable $var
	 * @return integer
	 */
	public function getVarIndex($var)
	{
		static $index;
		// todo: get index by other typecodes
		if ($var->typeCode>0) {
			$index += ceil($var->getWidth()/8);
		}
		else {
			$index++;
		}
		return $index;
	}
	
	/**
	 * Make documents (Record type 6)
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
			$bytes .= pack('A80', $this->fixstr($line)); // document line 80 bytes max
		}
		return $bytes;
	}
	
	/**
	 * Make additional (Record type 7)
	 * @return string bytes
	 */
	private function _additionalRecord()
	{
		$bytes = $this->_additional7_3(); // source system characteristics
		$bytes .= $this->_additional7_4(); // machine specific "float" type information.
		$bytes .= $this->_additional7_11(); // variable params
		$bytes .= $this->_additional7_13(); // extended names
		$bytes .= $this->_additional7_16(); // number of cases
		$bytes .= $this->_additional7_20(); // charset
		
		return $bytes;
	}
	
	/**
	 * SPSS Record Type 7 Subtype 3 - Source system characteristics
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
		$bytes .= pack('i', 1); // floatRepresentation
		$bytes .= pack('i', 1); // compressionScheme
		$bytes .= pack('i', 2); // endianCode (Little-endian)
		$bytes .= pack('i', 1251); // characterRepresentation (7-bit ASCII)
		
		return $bytes;
	}
	
	/**
	 * SPSS Record Type 7 Subtype 4 - Release and machine specific "float" type information. Added in SPSS release 4.0
	 * @return string bytes
	 */
	private function _additional7_4()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 4);
		$bytes .= pack('i', 8);
		$bytes .= pack('i', 3);
		$bytes .= pack('d', $this->sysmis); // system missing value
		$bytes .= pack('d', -$this->sysmis); // value for HIGHEST in missing values and recode
		$bytes .= pack('d', $this->sysmis); // value for LOWEST in missing values and recode
		
		return $bytes;
	}
	
	/**
	 * SPSS Record Type 7 Subtype 11 - Variable params
	 * @return string bytes
	 */
	private function _additional7_11()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 11);
		$bytes .= pack('i', 4); // size
		$bytes .= pack('i', count($this->variables) * 3); // count
		foreach($this->variables as $var) {
			$bytes .= pack('i', $var->measure);
			$bytes .= pack('i', $var->width);
			$bytes .= pack('i', $var->alignment);
		}
		
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 Subtype 13 - Variable extended names
	 * @return string bytes
	 */
	private function _additional7_13()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 13);
		$bytes .= pack('i', 1); // size
		$data = array();
		foreach($this->variables as $var) {
			$data[] = $var->shortName.'='.(!empty($var->name) ? $var->name : $var->shortName);
		}
		$data = implode("\t", $data);
		$datalen = strlen($data);
		$bytes .= pack('i', $datalen); // count
		$bytes .= pack('a'.$datalen, $this->fixstr($data));
		
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 Subtype 16 - Number of cases
	 * @return string bytes
	 */
	private function _additional7_16()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 16);
		$bytes .= pack('i', 8); // size
		$bytes .= pack('i', 2); // count
		$bytes .= pack('i', 1); // byte order
		$bytes .= pack('i', 0); 
		$bytes .= pack('d', 0); // count
		
		return $bytes;
	}

	/**
	 * SPSS Record Type 7 Subtype 20 - Charset
	 * @return string bytes
	 */
	private function _additional7_20()
	{
		$bytes = '';
		$bytes .= pack('i', self::RECORD_TYPE_ADDITIONAL);
		$bytes .= pack('i', 20);
		$bytes .= pack('i', 1); // size
		
		$bytes .= pack('i', strlen($this->charset)); // count
		$bytes .= pack('a*', $this->charset);
		
		return $bytes;
	}
	
	/**
	 * Make final (Record type 999)
	 * @return string bytes
	 */
	private function _finalRecord()
	{
		$bytes = pack('i', self::RECORD_TYPE_FINAL);
		$bytes .= pack('i', 0);
		return $bytes;
	}
	
	/**
	 * Make data
	 * @return string bytes
	 */
	private function _data()
	{
		$bytes = '';
		for($i=0;$i<$this->numberOfCases;$i++) {
			foreach($this->variables as $key => $var) {
				$varType = $var->getType();
				$value = isset($var->data[$i]) ? $var->data[$i] : null;
				if ($this->compression) {
					// @todo
				}
				else {
					if ($varType==SPSSVariable::TYPE_NUMERIC) {
						$bytes .= pack('d', $value);
					}
					else {
						$bytes .= pack('a'.$var->typeCode, $this->fixstr($value));
						$skipBytes = 0;
						if ($var->typeCode % 8){
							$skipBytes = 8 - ($var->typeCode % 8);
						}
						$bytes .= pack('x'.$skipBytes);
					}
				}
			}
		}
		return $bytes;
	}
	
	/**
	 * Fix string charset
	 * 
	 * @params string $str
	 * @return string
	 */
	private function fixstr($str)
	{
		if ($this->charset !='utf-8') {
			$str = iconv('utf-8', $this->charset, $str);
		}
		return $str;
	}
	
	/**
	 * Convert bytes array to integer
	 * 
	 * @params array $bytes
	 * @return integer
	 */
	public static function toInt($bytes)
	{
		return $bytes[3]<<24 | $bytes[2]<<16 | $bytes[1]<<8 | $bytes[0]<<0;
	}
}
