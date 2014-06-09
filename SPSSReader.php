<?php

defined('BIG_ENDIAN') OR define('BIG_ENDIAN', pack('L', 1) === pack('N', 1));

class SPSSException extends Exception {}

class SPSSReader
{
	const RECORD_TYPE_VARIABLE = 2;
	const RECORD_TYPE_VALUE_LABEL = 3;
	const RECORD_TYPE_VALUE_LABEL_INDEX = 4;
	const RECORD_TYPE_DOCUMENT  = 6;
	const RECORD_TYPE_DATA = 7;
	const RECORD_TYPE_END = 999;
	
	const FORMAT_TYPE_A        = 1;
	const FORMAT_TYPE_AHEX     = 2;
	const FORMAT_TYPE_COMMA    = 3;
	const FORMAT_TYPE_DOLLAR   = 4;
	const FORMAT_TYPE_F        = 5;
	const FORMAT_TYPE_IB       = 6;
	const FORMAT_TYPE_PIBHEX   = 7;
	const FORMAT_TYPE_P        = 8;
	const FORMAT_TYPE_PIB      = 9;
	const FORMAT_TYPE_PK       = 10;
	const FORMAT_TYPE_RB       = 11;
	const FORMAT_TYPE_RBHEX    = 12;
	const FORMAT_TYPE_Z        = 15;
	const FORMAT_TYPE_N        = 16;
	const FORMAT_TYPE_E        = 17;
	const FORMAT_TYPE_DATE     = 20;
	const FORMAT_TYPE_TIME     = 21;
	const FORMAT_TYPE_DATETIME = 22;
	const FORMAT_TYPE_ADATE    = 23;
	const FORMAT_TYPE_JDATE    = 24;
	const FORMAT_TYPE_DTIME    = 25;
	const FORMAT_TYPE_WKDAY    = 26;
	const FORMAT_TYPE_MONTH    = 27;
	const FORMAT_TYPE_MOYR     = 28;
	const FORMAT_TYPE_QYR      = 29;
	const FORMAT_TYPE_WKYR     = 30;
	const FORMAT_TYPE_PCT      = 31;
	const FORMAT_TYPE_DOT      = 32;
	const FORMAT_TYPE_CCA      = 33;
	const FORMAT_TYPE_CCB      = 34;
	const FORMAT_TYPE_CCC      = 35;
	const FORMAT_TYPE_CCD      = 36;
	const FORMAT_TYPE_CCE      = 37;
	const FORMAT_TYPE_EDATE    = 38;
	const FORMAT_TYPE_SDATE    = 39;
	
	public $header;
	public $specificInfo;
	public $specificFloatInfo;
	
	private $_file;
	private $_buffer;
	private $_cursor=0;
	
	public function __construct($file=null)
	{
		if ($file) {
			if (!@file_exists($file)) {
				throw new SPSSException(sprintf('File "%s" not exists', $file));
			}
			$this->_file = fopen($file,'r');
		}
		$this->_read();
	}
	
	public function __destruct()
	{
		if ($this->_file) {
			fclose($this->_file);
			$this->_buffer = '';
			$this->_cursor = 0;
		}
	}
	
	/**
     * This method returns the print / write format code of a variable. The 
     * returned value is a tuple consisting of the format abbreviation 
     * (string <= 8 chars) and a meaning (long string). Non-existent codes 
     * have a (null, null) tuple returned.
	 * 
	 * @param integer $type
	 * @return string
	 */
    public function getPrintWriteCode($type)
	{
	    switch($type) {
			case 0: return array('','Continuation of string variable');
			case self::FORMAT_TYPE_A: return array('A','Alphanumeric');
			case self::FORMAT_TYPE_AHEX: return array('AHEX', 'alphanumeric hexadecimal');
			case self::FORMAT_TYPE_COMMA: return array('COMMA', 'F format with commas');
			case self::FORMAT_TYPE_DOLLAR: return array('DOLLAR', 'Commas and floating point dollar sign');
			case self::FORMAT_TYPE_F: return array('F', 'F (default numeric) format');
			case self::FORMAT_TYPE_IB: return array('IB', 'Integer binary');
			case self::FORMAT_TYPE_PIBHEX: return array('PIBHEX', 'Positive binary integer - hexadecimal');
			case self::FORMAT_TYPE_P: return array('P', 'Packed decimal');
			case self::FORMAT_TYPE_PIB: return array('PIB', 'Positive integer binary (Unsigned)');
			case self::FORMAT_TYPE_PK: return array('PK', 'Positive packed decimal (Unsigned)');
			case self::FORMAT_TYPE_RB: return array('RB', 'Floating point binary');
			case self::FORMAT_TYPE_RBHEX: return array('RBHEX', 'Floating point binary - hexadecimal');
			case self::FORMAT_TYPE_Z: return array('Z', 'Zoned decimal');
			case self::FORMAT_TYPE_N: return array('N', 'N format - unsigned with leading zeros');
			case self::FORMAT_TYPE_E: return array('E', 'E format - with explicit power of ten');
			case self::FORMAT_TYPE_DATE: return array('DATE', 'Date format dd-mmm-yyyy');
			case self::FORMAT_TYPE_TIME: return array('TIME', 'Time format hh:mm:ss.s');
			case self::FORMAT_TYPE_DATETIME: return array('DATETIME', 'Date and time');
			case self::FORMAT_TYPE_ADATE: return array('ADATE', 'Date in mm/dd/yyyy form');
			case self::FORMAT_TYPE_JDATE: return array('JDATE', 'Julian date - yyyyddd');
			case self::FORMAT_TYPE_DTIME: return array('DTIME', 'Date-time dd hh:mm:ss.s');
			case self::FORMAT_TYPE_WKDAY: return array('WKDAY', 'Day of the week');
			case self::FORMAT_TYPE_MONTH: return array('MONTH', 'Month');
			case self::FORMAT_TYPE_MOYR: return array('MOYR', 'mmm yyyy');
			case self::FORMAT_TYPE_QYR: return array('QYR', 'q Q yyyy');
			case self::FORMAT_TYPE_WKYR: return array('WKYR', 'ww WK yyyy');
			case self::FORMAT_TYPE_PCT: return array('PCT', 'Percent - F followed by "%"');
			case self::FORMAT_TYPE_DOT: return array('DOT', 'Like COMMA, switching dot for comma');
			case self::FORMAT_TYPE_CCA: return array('CCA', 'User-programmable currency format');
			case self::FORMAT_TYPE_CCB: return array('CCB', 'User-programmable currency format');
			case self::FORMAT_TYPE_CCC: return array('CCC', 'User-programmable currency format');
			case self::FORMAT_TYPE_CCD: return array('CCD', 'User-programmable currency format');
			case self::FORMAT_TYPE_CCE: return array('CCE', 'User-programmable currency format');
			case self::FORMAT_TYPE_EDATE: return array('EDATE', 'Date in dd.mm.yyyy style');
			case self::FORMAT_TYPE_SDATE: return array('SDATE', 'Date in yyyy/mm/dd style');
			default: return array(null, null);
		}
    }
	
	/**
	 * Check is date format
	 * 
	 * @param integer $type
	 * @return boolean
	 */
    public function isDateFormat($type)
	{
        return $type == self::FORMAT_TYPE_DATE ||
			$type == self::FORMAT_TYPE_DATETIME ||
			$type == self::FORMAT_TYPE_ADATE ||
			$type == self::FORMAT_TYPE_JDATE ||
			$type == self::FORMAT_TYPE_SDATE ||
			$type == self::FORMAT_TYPE_EDATE ||
			$type == self::FORMAT_TYPE_QYR ||
			$type == self::FORMAT_TYPE_MOYR ||
			$type == self::FORMAT_TYPE_WKYR
		;
    }
	
	/**
	 * Read spss file
	 * 
	 * @return array
	 */
	private function _read()
	{
		$this->variables = array();
		$this->valueLabels = array();
		$this->valueLabelsIndex = array();
		$this->documents = array();
		$this->multiResponse = array();
		$this->dateVars = array();
		$this->miscInfo = array();
		
		// reset buffer
		$this->_buffer = '';
		$this->_cursor = 0;
		$stop = false;
		
		// General Inforamtion
		$this->header = $this->_readHeader();
		
		while (!$stop) {
			// record type
			$type = $this->readInt();
			
			switch($type) {
				// Variable Record
				case(self::RECORD_TYPE_VARIABLE):
					$this->_readVariable();
					break;
				
				// Value and labels
				case(self::RECORD_TYPE_VALUE_LABEL):
					$this->_readValueLabels();
					break;
				
				// Read and parse value label index records
				case(self::RECORD_TYPE_VALUE_LABEL_INDEX):
					$count = $this->readInt();
					for($i=0;$i<$count;$i++) {
						$result->valueLabelsIndex[] = $this->readInt();
					}
					break;
				
				// Read and parse document records
				case(self::RECORD_TYPE_DOCUMENT):
					$this->documents = $this->_readDocuments();
					break;
				
				// Read and parse additional records
				case(self::RECORD_TYPE_DATA):
					$subtype = $this->readInt();
					$size = $this->readInt();
					$count = $this->readInt();
					$datalen = $size * $count;
					switch($subtype) {
						// SpecificInfoReader
						case 3:
							$this->specificInfo = $this->_readSpecificInfo();
							break;
						// SpecificFloatInfoReader
						case 4:
							$this->specificFloatInfo = $this->_readSpecificFloatInfo();
							break;
						// VariableSetsReader
						case 5:
							$result->variableSets = $this->readString($datalen);
							break;
						// VariableTrendsReader
						// case 6:
							// get data array
							// $result->explicitPeriodFlag = $this->readInt();
							// $result->period = $this->readInt();
							// $result->numDateVars = $this->readInt();
							// $result->lowestIncr = $this->readInt();
							// $result->highestStart = $this->readInt();
							// $result->dateVarsMarker = $this->readInt();
							// for($i=0;$i<$result->numDateVars;$i++) {
								// $result->dateVars[] = array(
									// $this->readInt(),
									// $this->readInt(),
									// $this->readInt(),
								// );
							// }
							// break;
						// MultiResponseReader
						case 7:
							// TODO: parse
							$this->multiResponse = $this->readString($datalen);
							break;
						// VariableParamsReader
						case 11:
							if ($size != 4) {
								throw new SPSSException("Error reading record type 7 subtype 11: bad data element length [{$size}]. Expecting 4.");
							}
							if (($count % 3) != 0) {
								throw new SPSSException("Error reading record type 7 subtype 11: number of data elements [{$count}] is not a multiple of 3.");
							}
							$this->_readVariableParams($count / 3);
							break;
						// ExtendedNamesReader
						case 13:
							$data = $this->readString($datalen);
							$extendedNames = array();
							foreach(explode("\t", $data) as $row) {
								list($key,$value) = explode('=', $row);
								$extendedNames[$key] = $value;
							}
							$this->extendedNames = $extendedNames;
							break;
						// ExtendedStringsReader
						case 14:
							// TODO: parse
							$this->extendedStrings = $this->readString($datalen);
							break;
						// NumberOfCasesReader
						case 16:
							$data = new stdClass();
							$data->byteOrder = $this->readInt();
							$data->count = $this->readInt();
							$this->numberOfCases = $data;
							break;
						// DatasetAttributesReader
						case 17:
							$this->datasetAttributes = $this->readString($datalen);
							break;
						// VariableAttributesReader
						case 18:
							// TODO: parse
							$this->variableAttributes = $this->readString($datalen);
							break;
						// Charset
						case 20:
							$this->charset = $this->readString($datalen);
							break;
						// XML info
						case 24:
							$this->xmlInfo = $this->readString($datalen);
							break;
						// Other info
						default:
							$this->miscInfo[$subtype] = $this->readString($datalen);
							break;
					}
					break;
				
				// Finish
				case(self::RECORD_TYPE_END):
					$this->readInt();
					$this->_readData();
					$stop = true;
					break;
			}
		}
	}
	
	/**
	 * This method retrieves the actual data and stores them into the 
	 * appropriate variable's 'data' attribute.
	 * 
	 * @return void
	 **/
	private function _readData()
	{
		// read variables data
		$this->cluster = array();
		for($case=0;$case<$this->header->numberOfCases;$case++) {
			foreach($this->variables as $var) {
				// numeric
				if ($var->typeCode==0) {
					$var->data[] = $this->_readDataNumber();
				}
				//string
				elseif ($var->typeCode > 0 && $var->typeCode < 256) {
					$var->data[] = $this->_readDataString($var);
				}
			}
		}
	}
	
	/**
	 * This method is called when a number / numeric datum is to be 
	 * retrieved. This method returns "False" (the string, not the Boolean 
	 * because of conflicts when 0 is returned) if the operation is not 
	 * possible.
	 * 
	 * @param object $var
	 * @return string
	 **/
	private function _readDataNumber()
	{
        if ($this->header->compressionSwitch == 0) { // uncompressed number
			return $this->readDouble();
		}
		else { // compressed number
			if (sizeof($this->cluster) == 0) { // read new bytecodes
				$bytes = $this->read(8);
				foreach($bytes as $byte) {
					$this->cluster[] = $byte;
				}
			}
			$byte = array_shift($this->cluster);
			
			if ($byte > 1 && $byte < 252) {
				return $byte - 100;
			}
			elseif ($byte == 252) {
				return "False";
			}
			elseif ($byte == 253) {
				return $this->readDouble();
			}
			elseif ($byte == 254) {
				return 0.0;
			}
			elseif ($byte == 255) {
				return $this->specificFloatInfo->systemMissingValue;
			}
		}
	}
	
	/**
	 * This method is called when a string is to be retrieved. Strings can be 
	 * longer than 8-bytes long if so indicated. This method returns systemMissingValue 
     * (the string not the Boolean) is returned due to conflicts. 
	 * 
	 * @param object $var
	 * @return string
	 **/
	private function _readDataString($var)
	{
        if ($this->header->compressionSwitch == 0) { // uncompressed string
			return $this->readSring(8);
		}
		else { // compressed string
			$ln = '';
			while(1) {
				if (sizeof($this->cluster) == 0) { // read new bytecodes
					$bytes = $this->read(8);
					foreach($bytes as $byte) {
						$this->cluster[] = $byte;
					}
				}
				$byte = array_shift($this->cluster);
				if ($byte > 1 && $byte < 252) {
					return $byte - 100;
				}
				elseif ($byte == 252) {
					return $this->specificFloatInfo->systemMissingValue;
				}
				elseif ($byte == 253) {
					$bytes = $this->read(8);
					if (sizeof($bytes) < 1) {
						return $this->specificFloatInfo->systemMissingValue;
					}
					else {
						$ln .= $this->packBytes($bytes);
						if (strlen($ln) > $var->typeCode) {
							return $ln;
						}
					}
				}
				elseif ($byte == 254) {
					if ($ln!='') {
						return $ln;
					}
				}
				elseif ($byte == 255) {
					return $this->specificFloatInfo->systemMissingValue;
				}
			}
		}
	}
	
	/**
	 * Read and parse general information
	 * This method reads in a type 1 record (file meta-data).
	 * 
	 * @return object
	 */
	private function _readHeader()
	{
		$data = new stdClass();
		$data->recType = $this->readString(4);
		$data->dumpInfo = $this->readString(60);
		$data->layoutCode = $this->readInt();
		$data->numberOfVariables = $this->readInt();
		$data->compressionSwitch = $this->readInt();
		$data->caseWeightVariable = $this->readInt();
		$data->numberOfCases = $this->readInt();
		$data->compressionBias = $this->readDouble();
		$data->creationDate = $this->readString(9) .' '. $this->readString(8);
		$data->fileLabel = $this->readString(64);
		$this->skipBytes(3);
		return $data;
	}
	
	/**
	 * Read Variable
	 * This method reads in a type 2 record (variable meta-data).
	 * 
	 * @return object
	 */
	private function _readVariable()
	{
		$var = new stdClass();
		$var->typeCode = $this->readInt();
		
		if ($var->typeCode!=-1) {
			
			$var->labelFlag = $this->readInt();
			$var->missingValueFormat = $this->readInt();
			$var->printFormatCode = $this->readInt();
			$var->printFormat = array(
				'decimalPlaces' => ($var->printFormatCode & 0x000000FF),
				'width' => ($var->printFormatCode & 0x0000FF00) >> 8,
				'type' => $this->getPrintWriteCode(($var->printFormatCode & 0x00FF0000) >> 16),
			);
			$var->writeFormatCode = $this->readInt();
			$var->writeFormat = array(
				'decimalPlaces' => ($var->writeFormatCode & 0x000000FF),
				'width' => ($var->writeFormatCode & 0x0000FF00) >> 8,
				'type' => $this->getPrintWriteCode(($var->writeFormatCode & 0x00FF0000) >> 16),
			);
			$var->name = $this->readString(8); // 8-byte variable name
			if ($var->labelFlag==1) {
				$var->labelLength = $this->readInt();
				if (($var->labelLength % 4) != 0) {
					$var->labelLength = $var->labelLength + 4 - ($var->labelLength % 4);
				}
				$var->label = $this->readString($var->labelLength); // longer string label
			}
			else {
				$var->label = '';
			}
			$var->missingValues = array();
			if ($var->missingValueFormat!=0) {
				for($i=0;$i<abs($var->missingValueFormat);$i++) {
					$var->missingValues[] = $this->readDouble();
				}
			}
		}
		else {
			// read the rest
		}
		
		$var->params = array();
		$var->data = array();
		$this->variables[] = $var;
	}
	
	/**
	 * Read Variable params
	 * 
	 * @return void
	 */
	private function _readVariableParams($count_variables)
	{
		$measure = array("Nominal", "Ordinal", "Scalar");
		$align = array("Left", "Right", "Center");
		
		for ($i = 0; $i < $count_variables; $i++) {
			$param = new stdClass();
			$param->measure = $this->readInt();
			$param->measureLabel = isset($measure[$param->measure-1]) ? $measure[$param->measure-1] : null;
			$param->width = $this->readInt();
			$param->align = $this->readInt();
			$param->alignLabel = isset($align[$param->align]) ? $align[$param->align] : null;
			$this->variables[$i]->params = $param;
		}
	}
	
	/**
	 * Read valuelabels information
     * This method reads in a type 3. Type 3 is a value label record (value-field pairs for 
     * labels), and type 4 is the variable index record (which variables 
     * have these value-field pairs).
	 * 
	 * @return object
	 */
	private function _readValueLabels()
	{
		$data = array();
		$labelCount = $this->readInt();
		// do for each pair
		for($i=0; $i < $labelCount; $i++) {
			$value = $this->readDouble();
            $l = ord($this->readString());
            if (($l % 8) != 0) {
                $l = $l + 8 - ($l % 8);
			}
			$data[$value] = trim( $this->readString($l-1) );
		}
		$this->valueLabels[] = $data;
	}
	
	/**
	 * Read documents
	 * 
	 * @return object
	 */
	private function _readDocuments()
	{
		$data = array();
		$count = $this->readInt();
		for ($i=0; $i < $count; $i++) {
			$data[] = $this->readString(80);
		}
		return $data;
	}
	
	/**
	 * Read Specific information
	 * 
	 * @return object
	 */
	private function _readSpecificInfo()
	{
		// this is for release and machine-specific information
		$floating = array("IEEE","IBM 370", "DEC VAX E");
        $endian = array("Big-endian","Little-endian");
        $character = array("EBCDIC","7-bit ASCII","8-bit ASCII","DEC Kanji");
		
		$data = new stdClass();
		$data->releaseNumber = $this->readInt();
		$data->releaseSubNumber = $this->readInt();
		$data->releaseIdentifier = $this->readInt();
		$data->machineCode = $this->readInt();
		$data->floatingPointCode = $this->readInt();
		$data->floatingPointName = isset($floating[$data->floatingPointCode-1]) ? $floating[$data->floatingPointCode-1] : null;
		$data->compressionSchemeCode = $this->readInt();
		$data->endianCode = $this->readInt();
		$data->endianName = isset($endian[$data->endianCode-1]) ? $endian[$data->endianCode-1] : null;
		$data->charRepresentationCode = $this->readInt();
		$data->charRepresentationName = isset($character[$data->charRepresentationCode-1]) ? $character[$data->charRepresentationCode-1] : null;
		
		return $data;
	}
	
	/**
	 * Read Specific float information
	 * 
	 * @return object
	 */
	private function _readSpecificFloatInfo()
	{
		$data = new stdClass();
		$data->systemMissingValue = $this->readDouble();
		$data->highest = $this->readDouble();
		$data->lowest = $this->readDouble();
		return $data;
	}
	
	/**
	 * Read more bytes
	 * 
	 * @param int $num
	 * @param int $pos
	 * @return array
	 */
	private function read($num=1, $pos=-1, $unpackFormat='C*')
	{
		if (!$this->_file) {
			return;
		}
		if ($pos >= 0) {
			$this->_cursor = $pos;
		}
		// do we need more data?		
		if ($this->_cursor + $num-1 >= strlen($this->_buffer)) {
			$response = null;
			$end = ($this->_cursor + $num);
			while (strlen($this->_buffer) < $end && $response !== false) {
				$need = $end - ftell($this->_file);
				if ($response = fread($this->_file, $need))
					$this->_buffer .= $response;
				else
					// throw new SPSSException('Unexpected termination');
					break;
			}
		}
		$result = substr($this->_buffer, $this->_cursor, $num);
		$this->_cursor += $num;
		if ($unpackFormat) {
			// we are dealing with bytes here, so force the encoding
			// $result = mb_convert_encoding($result, "8BIT");
			$result = unpack($unpackFormat, $result);
			return array_values($result);
		}
		return $result;
	}
	
	/**
	 * Read string bytes
	 * 
	 * @param int $num
	 * @param int $pos
	 * @return string
	 */
	private function readString($num=1)
	{
		return $this->packBytes($this->read($num));
	}
	
	/**
	 * Read integer
	 * 
	 * @retunr integer
	 */
	private function readInt()
	{
		// $bytes = $this->read(4,-1,false);
		// if (BIG_ENDIAN) {
			// $bytes = strrev($bytes);
		// }
		// $bytes = unpack("i",$bytes);
		// return $bytes[1];
		$bytes = $this->read(4);
        return ($bytes[3] & 0xff) << 24 | ($bytes[2] & 0xff) << 16 | ($bytes[1] & 0xff) << 8 | $bytes[0] & 0xff;
    }
	
	/**
	 * Read big integer
	 * 
	 * @retunr integer
	 */
    private function readBigInt()
	{
		$bytes = $this->read(4);
        return ($bytes[0] & 0xff) << 24 | ($bytes[1] & 0xff) << 16 | ($bytes[2] & 0xff) << 8 | $bytes[3] & 0xff;
    }
	
	/**
	 * Read Short
	 * 
	 * @retunr integer
	 */
    private function readShort()
	{
		$bytes = $this->read(2);
        return ($bytes[0] & 0xff) | ($bytes[1] & 0xff) << 8;
    }
	
	/**
	 * Read Big Short
	 * 
	 * @retunr integer
	 */
    private function readBigShort($bytes=null)
	{
		$bytes = $this->read(2);
        return ($bytes[0] & 0xff) << 8 | ($bytes[1] & 0xff);
    }
	
	/**
	 * Reads the 8 bytes and interprets them as a double precision floating point value
	 * 
	 * @return string
	 */
	private function readDouble()
	{
		$bytes = $this->readString(8,-1,false);
		if (BIG_ENDIAN) {
			$bytes = strrev($bytes);
		}
		$bytes = unpack("d",$bytes);
		return $bytes[1];
	}
	
	/**
	 * Skip bytes
	 * 
	 * @param int $num
	 * @return void
	 */
    private function skipBytes($num)
	{
		$this->_cursor += (int) $num;
    }
	
	/**
	 * Show bytes
	 * 
	 * @param array $bytes
	 * @return string
	 */
	private function packBytes($bytes)
	{
		$str='';
		foreach($bytes as $byte) {
			$str.=chr($byte);
		}
		return $str;
	}
}
