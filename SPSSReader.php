<?php
/**
 * SPSS Php Reader
 * 
 * @package fom.ru
 * @author vk.tiamo@gmail.com
 * @dependence php5.2+ version
 */

class SPSSException extends Exception {}

class SPSSVariable
{
	const TYPE_NUMERIC			= 1;
	const TYPE_STRING			= 2;
	
	const FORMAT_TYPE_A			= 1;
	const FORMAT_TYPE_AHEX		= 2;
	const FORMAT_TYPE_COMMA		= 3;
	const FORMAT_TYPE_DOLLAR	= 4;
	const FORMAT_TYPE_F			= 5;
	const FORMAT_TYPE_IB		= 6;
	const FORMAT_TYPE_PIBHEX	= 7;
	const FORMAT_TYPE_P			= 8;
	const FORMAT_TYPE_PIB		= 9;
	const FORMAT_TYPE_PK		= 10;
	const FORMAT_TYPE_RB		= 11;
	const FORMAT_TYPE_RBHEX		= 12;
	const FORMAT_TYPE_Z			= 15;
	const FORMAT_TYPE_N			= 16;
	const FORMAT_TYPE_E			= 17;
	const FORMAT_TYPE_DATE		= 20;
	const FORMAT_TYPE_TIME		= 21;
	const FORMAT_TYPE_DATETIME	= 22;
	const FORMAT_TYPE_ADATE		= 23;
	const FORMAT_TYPE_JDATE		= 24;
	const FORMAT_TYPE_DTIME		= 25;
	const FORMAT_TYPE_WKDAY		= 26;
	const FORMAT_TYPE_MONTH		= 27;
	const FORMAT_TYPE_MOYR		= 28;
	const FORMAT_TYPE_QYR		= 29;
	const FORMAT_TYPE_WKYR		= 30;
	const FORMAT_TYPE_PCT		= 31;
	const FORMAT_TYPE_DOT		= 32;
	const FORMAT_TYPE_CCA		= 33;
	const FORMAT_TYPE_CCB		= 34;
	const FORMAT_TYPE_CCC		= 35;
	const FORMAT_TYPE_CCD		= 36;
	const FORMAT_TYPE_CCE		= 37;
	const FORMAT_TYPE_EDATE		= 38;
	const FORMAT_TYPE_SDATE		= 39;
	
	public $typeCode = -1; // default is continue var code
	public $name; // < The full variable name
	public $shortName; // < The short variable name (8 characters max)
	public $label;
	public $hasLabel;
	public $missingValueFormatCode;
	public $missingValues=array();
	public $printFormatCode;
	public $printFormatDecimals;
	public $printFormatWidth;
	public $printFormatType;
	public $printFormatZero;
	public $writeFormatCode;
	public $writeFormatDecimals;
	public $writeFormatWidth;
	public $writeFormatType;
	public $writeFormatZero;
	
	public $valueLabels=array();
	public $extendedStringVars = array();
	public $extendedStringLength = 0;
	public $isExtended = 0;
	
	public $measure = -1; // < 1=nominal, 2=ordinal, 3=scale (copied from record type 7 subtype 11) */
	public $width = -1; // < display width (copied from record type 7 subtype 11) */
	public $alignment = -1; // < 0=left 1=right, 2=center (copied from record type 7 subtype 11) */
	
	public $data = array();
	
	public function getPrintFormat() {
		return self::getFormatInfo($this->printFormatType);
	}
	
	public function getWriteFormat() {
		return self::getFormatInfo($this->writeFormatType);
	}
	
	/**
	 * @return A integer containing the kind of type
	 */
	public function getType() {
		if ($this->typeCode == 0) {
			return self::TYPE_NUMERIC;
		}
		else {
			return self::TYPE_STRING;
		}
	}
	
	/**
	 * @return A string containing the variable name (empty if no label is available)
	 */
	public function getLabel() {
		return $this->label;
	}
	
	/**
	 * @return A string containing the kind of type
	 */
	public function getTypeLabel() {
		return $this->typeCode==0 ? 'Numeric' : 'String';
	}
	
	/**
	 * @return A string containing the kind of missing values
	 */
	public function getMissingLabel() {
		return $this->missingValues ? implode(', ', $this->missingValues) : 'None';
	}
	
	/**
	 * @return A string containing the kind of width
	 */
	public function getWidth() {
		if ($this->extendedStringLength) {
			$width = $this->extendedStringLength;
		}
		else {
			$width = $this->writeFormatWidth;
		}
		return $width;
	}
	
	/**
	 * Retrieves the SPSS write format number of decimals.
	 * 
	 * @return the length
	 */
	public function getDecimals() {
		return $this->writeFormatDecimals;
	}
	
	/**
	 * @return A string containing the kind of measure
	 */
	public function getAlignmentLabel() {
		$label = "";
		switch ($this->alignment) {
		case 0:
			$label = "Left";
			break;
		case 1:
			$label = "Center";
			$break;
		case 2:
			$label = "Right";
			break;
		}
		return $label;
	}
	
	/**
	 * @return A string containing the kind of measure
	 */
	public function getMeasureLabel() {
		$label = "";
		switch ($this->measure) {
		case 1:
			$label = "Nominal";
			break;
		case 2:
			$label = "Ordinal";
			break;
		case 3:
			$label = "Scale";
			break;
		}
		return $label;
	}
	
	/**
	 * @return A string containing the kind of columns
	 */
	public function getColumns() {
		return $this->width;
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
	public static function getFormatInfo($type)
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
			case self::FORMAT_TYPE_CCA: return array('CCA', 'User-programmable currency format (1)');
			case self::FORMAT_TYPE_CCB: return array('CCB', 'User-programmable currency format (2)');
			case self::FORMAT_TYPE_CCC: return array('CCC', 'User-programmable currency format (3)');
			case self::FORMAT_TYPE_CCD: return array('CCD', 'User-programmable currency format (4)');
			case self::FORMAT_TYPE_CCE: return array('CCE', 'User-programmable currency format (5)');
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
	public static function isDateFormat($type)
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
}

class SPSSReader
{
	public $header;
	public $specificInfo;
	public $specificFloatInfo;
	// variables
	public $variables = array();
	// documents
	public $documents = array();
	// long names dictionary
	public $extendedNames = array();
	// long strings dictionary
	public $extendedStrings = array();
	public $extendedValueLabels = array();
	public $dataStartPosition = 0;
	public $isBigEndian = false;
	// file descriptor
	private $_file;
	private $_cursor=0;
	private $varIndex=0;
	
	public function __construct($file=null)
	{
		if ($file) {
			if (!@file_exists($file)) {
				throw new SPSSException(sprintf('File "%s" not exists', $file));
			}
			$this->_file = fopen($file,'r');
		}
		// start reading file
		$this->_read();
	}
	
	public function __destruct()
	{
		if ($this->_file) {
			fclose($this->_file);
		}
	}
	
	/**
	 * Gets a SPSSVariable based on its 0-based file index position.
	 * 
	 * @return SPSSVariable
	 */
	public function getVariable($index)
	{
		if (isset($this->variables[$index])) {
			return $this->variables[$index];
		}
	}

	/**
	 * Returns the total number of variables in the file.
	 * 
	 * @return the number of records in the file
	 */
	public function getVariableCount() {
		return count($this->variables);
	}
	
	/**
	 * Read spss file
	 * 
	 * @return array
	 */
	private function _read()
	{
		$this->variableSets = array();
		$this->variableTrends = array();
		$this->datasetAttributes = array();
		$this->variableAttributes = array();
		$this->numberOfCases = array();
		$this->multiResponse = array();
		$this->xmlInfo = array();
		$this->miscInfo = array();
		$this->charset = '';
		
		$this->_cursor = 0;
		$stop = false;
		
		// General Inforamtion
		$this->header = $this->_readHeader();
		
		while (!$stop) {
			// record type
			$recordType = $this->readInt();
			
			switch($recordType) {
				// Variable Record
				case 2:
					$this->_readVariable();
					break;
				
				// Value and labels
				case 3:
					$this->_readValueLabels();
					break;
				
				// Read and parse document records
				case 6:
					$this->documents = $this->_readDocuments();
					break;
				
				// Read and parse additional records
				case 7:
					$subtype = $this->readInt();
					$size = $this->readInt();
					$count = $this->readInt();
					$datalen = $size * $count;
					switch($subtype) {
						
						// SPSS Record Type 7 Subtype 3 - Specific Info
						case 3:
							$this->specificInfo = $this->_readSpecificInfo();
							break;
						
						// SPSS Record Type 7 Subtype 4 - Specific Float Info
						case 4:
							$this->specificFloatInfo = $this->_readSpecificFloatInfo();
							break;
						
						// SPSS Record Type 7 Subtype 5 - Variable sets
						case 5:
							$this->variableSets = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 6 - Variable trends
						case 6:
							// get data array
							// $this->explicitPeriodFlag = $this->readInt();
							// $this->period = $this->readInt();
							// $this->numDateVars = $this->readInt();
							// $this->lowestIncr = $this->readInt();
							// $this->highestStart = $this->readInt();
							// $this->dateVarsMarker = $this->readInt();
							// for($i=0;$i<$this->numDateVars;$i++) {
								// $this->dateVars[] = array(
									// $this->readInt(),
									// $this->readInt(),
									// $this->readInt(),
								// );
							// }
							$this->variableTrends = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 7 - Multi response
						case 7:
							$data = $this->readString($datalen);
							$data = trim($data);
							foreach(explode("\n", $data) as $row) {
								list($key,$value) = explode('=', $row);
								if (!empty($key)) {
									$this->multiResponse[$key] = trim($value);
								}
							}
							break;
						
						// SPSS Record Type 7 Subtype 11 - Variable params
						case 11:
							if ($size != 4) {
								throw new SPSSException("Error reading record type 7 subtype 11: bad data element length [{$size}]. Expecting 4.");
							}
							if (($count % 3) != 0) {
								throw new SPSSException("Error reading record type 7 subtype 11: number of data elements [{$count}] is not a multiple of 3.");
							}
							$numberOfVariables = $count / 3;
							// reindex variables
							$this->variables = array_values($this->variables);
							for ($i = 0; $i < $numberOfVariables; $i++) {
								$var = $this->getVariable($i);
								$var->measure = $this->readInt();
								$var->width = $this->readInt();
								$var->alignment = $this->readInt();
							}
							break;
						
						// SPSS Record Type 7 Subtype 13 - Extended names
						case 13:
							$data = $this->readString($datalen);
							foreach(explode("\t", $data) as $row) {
								list($key,$value) = explode('=', $row);
								if (!empty($key)) {
									$this->extendedNames[$key] = trim($value);
								}
							}
							break;
						
						// SPSS Record Type 7 Subtype 14 - Extended strings
						case 14:
							$data = $this->readString($datalen);
							$data = trim($data);
							foreach(explode("\t", $data) as $row) {
								list($key,$value) = explode('=', $row);
								if (!empty($key)) {
									$this->extendedStrings[$key] = trim($value);
								}
							}
							break;
						
						// SPSS Record Type 7 Subtype 16 - Number Of Cases
						case 16:
							$data = new stdClass();
							$data->byteOrder = $this->readInt();
							$data->count = $this->readInt();
							$this->numberOfCases = $data;
							break;
						
						// SPSS Record Type 7 Subtype 17 - Dataset Attributes
						case 17:
							$this->datasetAttributes = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 18 - Variable Attributes
						case 18:
							$data = $this->readString($datalen);
							$data = trim($data);
							foreach(explode("/", $data) as $row) {
								list($key,$value) = explode(':', $row);
								if (!empty($key)) {
									$this->variableAttributes[$key] = trim($value);
								}
							}
							break;
						
						// SPSS Record Type 7 Subtype 20 - Charset
						case 20:
							$this->charset = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 21 - Extended value labels
						case 21:
							$this->extendedValueLabels = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 24 - XML info
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
				case 999:
					if ($this->readInt() != 0) {
						throw new SPSSException("Error reading record type 999: Non-zero value found.");
					}
					// This location s where the data starts
					$this->dataStartPosition = $this->_cursor;
					$stop = true;
					
					// prepare variables
					$chunks = 0; $parent = 0;
					foreach($this->variables as $index => &$var) {
						// extended names
						if (isset($this->extendedNames[$var->shortName])) {
							$var->name = $this->extendedNames[$var->shortName];
						}
						// extended values
						if (isset($this->extendedStrings[$var->shortName])) {
							$var->extendedStringLength = $this->extendedStrings[$var->shortName];
							// the maximum size of the variable is 255
							$chunks = ceil($var->extendedStringLength / 255) - 1;
							$parent = $index;
							continue;
						}
						if ($chunks>0) {
							$var->isExtended = $parent;
							$this->variables[$parent]->extendedStringVars[] = $index;
							$chunks--;
						}
					}
					
					break;
				default:
					// throw new SPSSException("Read error: invalid record type [" . $recordType . "]");
			}
		}
	}
	
	/**
	 * SPSS Record Type 1 - General information
	 * 
	 * @return object
	 */
	private function _readHeader()
	{
		$data = new stdClass();
		
		// signature
		$data->recType = $this->readString(4);
		if ($data->recType != '$FL2') {
			throw new SPSSException('Read header error: this is not a valid SPSS file. Does not start with $FL2.');
		}
		
		// identification
		$data->dumpInfo = trim($this->readString(60));
		// layout code
		$data->layoutCode = $this->readInt();
		
		// --> layoutCode should be 2 or 3.
		// --> If not swap bytes and check again which would then indicate big-endian
		if ($data->layoutCode!=2 && $data->layoutCode!=3) {
			// try to flip to big-endian mode and read again
			$this->isBigEndian = true;
			$this->skipBytes(-4);
			$data->layoutCode = $this->readInt();
		}
		
		// OBS
		$data->numberOfVariables = $this->readInt();
		// compression
		$data->compressionSwitch = $this->readInt();
		// weight
		$data->caseWeightVariable = $this->readInt();
		// cases
		$data->numberOfCases = $this->readInt();
		// $data->numberOfCases = 100; // @test
		// compression bias
		$data->compressionBias = $this->readDouble();
		// creation date
		$data->creationDate = $this->readString(9) .' '. $this->readString(8);
		// file label
		$data->fileLabel = $this->readString(64);
		// padding
		$this->skipBytes(3);
		
		return $data;
	}
	
	/**
	 * SPSS Record Type 2 - Variable information
	 * 
	 * @return object
	 */
	private function _readVariable()
	{
		$var = new SPSSVariable();
		$var->typeCode = $this->readInt();
		
		// ignore string continuation records (typeCode = -1)
		if ($var->typeCode>=0) {
			
			// read label flag
			$var->hasLabel = $this->readInt();
			// read missing value format code
			$var->missingValueFormatCode = $this->readInt();
			if (abs($var->missingValueFormatCode) > 3) {
				throw new SPSSException("Error reading variable Record: invalid missing value format code [" . $var->missingValueFormatCode . "]. Range is -3 to 3.");
			}
			// read print format code
			$var->printFormatCode = $this->readInt();
			$var->printFormatDecimals = ($var->printFormatCode >> 0) & 0xFF; // byte 1
			$var->printFormatWidth = ($var->printFormatCode >> 8) & 0xFF; // byte 2
			$var->printFormatType = ($var->printFormatCode >> 16) & 0xFF; // byte 3
			$var->printFormatZero = ($var->printFormatCode >> 24) & 0xFF; // byte 4
			// read write format code
			$var->writeFormatCode = $this->readInt();
			$var->writeFormatDecimals = ($var->writeFormatCode >> 0) & 0xFF; // byte 1
			$var->writeFormatWidth = ($var->writeFormatCode >> 8) & 0xFF; // byte 2
			$var->writeFormatType = ($var->writeFormatCode >> 16) & 0xFF; // byte 3
			$var->writeFormatZero = ($var->writeFormatCode >> 24) & 0xFF; // byte 4
			// read varname
			$var->shortName = trim($this->readString(8)); // 8-byte variable name
			$var->name = $var->shortName;
			// read label length and label only if a label exists
			if ($var->hasLabel==1) {
				$var->labelLength = $this->readInt();
				$var->label = $this->readString($var->labelLength);
				// variableRecord labels are stored in chunks of 4-bytes
				// --> we need to skip unused bytes in the last chunk
				if ($var->labelLength % 4 != 0) {
					$this->skipBytes(4 - ($var->labelLength % 4));
				}
			}
			// missing values
			if ($var->missingValueFormatCode!=0) {
				for($i=0;$i<abs($var->missingValueFormatCode);$i++) {
					$var->missingValues[] = $this->readDouble();
				}
			}
			$this->variables[$this->varIndex] = $var;
		}
		// if TYPECODE is -1, record is a continuation of a string var
		// else {
			// read and ignore the next 24 bytes
			// $this->skipBytes(24);
		// }
		
		$this->varIndex++;
	}
	
	/**
	 * SPSS Record Type 3 - Value labels
	 * 
	 * @return void
	 */
	private function _readValueLabels()
	{
		$data = array();
		
		// number of labels
		$numberOfLabels = $this->readInt();
		
		// labels
		for($i=0; $i < $numberOfLabels; $i++) {
			// read the label value
			$value = $this->readDouble();
			
			// read the length of a value label
			// the following byte in an unsigned integer (max value is 60)
			$labelLength = $this->read(1,false);
			$labelLength = ord($labelLength);
			
			if ($labelLength > 255) {
				throw new SPSSException("The length of a value label({$labelLength}) must be less than 256: $!");
			}
			
			// read the label
			$label = $this->readString($labelLength);
			// value labels are stored in chunks of 8-bytes with space allocated
			// for length+1 characters
			// --> we need to skip unused bytes in the last chunk
			if (($labelLength+1) % 8){
				$this->skipBytes(8 - (($labelLength+1) % 8));
			}
			
			$data[$value] = $label;
		}
		
		// read type 4 record (that must follow type 3!)
		
		// record type
		$recordTypeCode = $this->readInt();
		if ($recordTypeCode != 4){
			throw new SPSSException("Error reading Variable Index record: bad record type [" . $recordTypeCode . "]. Expecting Record Type 4.");
		}
		
		// number of variables
		$numberOfVariables  = $this->readInt();
		// variableRecord indexes
		for($i=0;$i<$numberOfVariables ;$i++) {
			$varIndex = $this->readInt() - 1;
			if (isset($this->variables[$varIndex])) {
				$this->variables[$varIndex]->valueLabels = $data;
			}
		}
	}
	
	/**
	 * SPSS Record Type 6 - Document record
	 * 
	 * @return array
	 */
	private function _readDocuments()
	{
		$line = array();
		// number of variables
		$numberOfLines = $this->readInt();
		// read the lines
		for ($i=0; $i < $numberOfLines; $i++) {
			$line[] = $this->readString(80);
		}
		return $line;
	}
	
	/**
	 * SPSS Record Type 7 Subtype 3 - Release and machine specific "integer" type information. Added in SPSS release 4.0
	 * 
	 * @return object
	 */
	private function _readSpecificInfo()
	{
		$float = array(null, "IEEE", "IBM 370", "DEC VAX E");
		$endian = array(null, "Big-endian", "Little-endian");
		$character = array(null, "EBCDIC", "7-bit ASCII", "8-bit ASCII", "DEC Kanji");
		
		$data = new stdClass();
		$data->releaseMajor = $this->readInt();
		$data->releaseMinor = $this->readInt();
		$data->releaseSpecial = $this->readInt();
		$data->machineCode = $this->readInt();
		$data->floatRepresentation = $this->readInt();
		$data->floatRepresentationLabel = !empty($float[$data->floatRepresentation]) ? $float[$data->floatRepresentation] : "Unknown";
		$data->compressionScheme = $this->readInt();
		$data->endianCode = $this->readInt();
		$data->endianLabel = !empty($endian[$data->endianCode]) ? $endian[$data->endianCode] : "Unknown";
		$data->characterRepresentation = $this->readInt();
		$data->characterRepresentationLabel = !empty($character[$data->characterRepresentation]) ? $character[$data->characterRepresentation] : "Unknown";
		
		return $data;
	}
	
	/**
	 * SPSS Record Type 7 Subtype 4 - Release and machine specific "float" type information. Added in SPSS release 4.0
	 * 
	 * @return object
	 */
	private function _readSpecificFloatInfo()
	{
		$data = new stdClass();
		// system missing value
		$data->sysmis = $this->readDouble();
		// value for HIGHEST in missing values and recode
		$data->highest = $this->readDouble();
		// value for LOWEST in missing values and recode
		$data->lowest = $this->readDouble();
		
		return $data;
	}
	
	/**
	 * This method retrieves the actual data and stores them into the 
	 * appropriate variable's 'data' attribute.
	 * 
	 * @param integer $varIndex
	 * @return void
	 */
	private $cluster = array(); // 8-byte cluster for compressed files (this value is retained between calls)
	private $clusterIndex = 8; // for compressed files (once initialized, this value is retained between calls)
	public function loadData()
	{
		if ($this->dataStartPosition < 1) {
			// this has not been initialized, we don't actually know where the data starts
			throw new SPSSException("Error: data location pointer not initialized.");
		}
		// seek file pointer
		$this->_cursor = $this->dataStartPosition;
		
		for ($i = 0; $i < $this->header->numberOfCases; $i++) {
			
			foreach($this->variables as $index => $var) {
				
				$varType = $var->getType();
				
				// compute number of blocks used by this variable
				$blocksToRead = 0;
				// Number of data storage blocks used by the current variable
				$charactersToRead = 0;
				
				// init
				if ($varType==SPSSVariable::TYPE_NUMERIC) {
					$numData = 'NaN';
					$blocksToRead = 1;
				}
				else {
					// string: depends on string length but always in blocks of 8 bytes
					$charactersToRead = $var->typeCode;
					$blocksToRead = floor( (($charactersToRead - 1) / 8) + 1 );
					$strData = "";
				}
				
				// read the variable from the file
				while ($blocksToRead > 0) {
					// $this->log("REMAINING #blocks =" . $blocksToRead);
					if ($this->header->compressionSwitch>0) {
						
						/* COMPRESSED DATA FILE */
						
						// $this->log("cluster index " . $this->clusterIndex);
						if ($this->clusterIndex > 7) {
							// $this->log("READ CLUSTER");
							// need to read a new compression cluster of up to 8 variables
							$this->cluster = $this->read(8);
							$this->clusterIndex = 0;
						}
						// convert byte to an unsigned byte in an int
						$byteValue = (0x000000FF & (int) $this->cluster[$this->clusterIndex]);
						// $this->log("Variable ". $var->name ." cluster byte".$this->clusterIndex."=".$byteValue);
						$this->clusterIndex++;
						
						switch ($byteValue) {
						case 0: // skip this code
							break;
						case 252: // end of file, no more data to follow. This should not happen.
							throw new SPSSException("Error reading data: unexpected end of compressed data file (cluster code 252)");
						break;
						case 253: // data cannot be compressed, the value follows the cluster
							if ($varType==SPSSVariable::TYPE_NUMERIC) {
								$numData = $this->readDouble();
							}
							else { // STRING
								// read a maximum of 8 characters but could be less if this is the last block
								$blockStringLength = min(8, $charactersToRead);
								
								// append to existing value
								$strData .= $this->readString($blockStringLength);
								// if this is the last block, skip the remaining dummy byte(s) (in the block of 8 bytes)
								if ($charactersToRead < 8) {
									$this->skipBytes(8 - $charactersToRead);
								}
								// update the characters counter
								$charactersToRead -= $blockStringLength;
							}
							break;
						case 254: // all blanks
							if ($varType==SPSSVariable::TYPE_NUMERIC) {
								// note: not sure this is used for numeric values (?)
								$numData = '0.0';
							}
							else {
								// append 8 spaces to existing value
								$strData .= "        ";
							}
							break;
						case 255: // system missing value
							if ($varType==SPSSVariable::TYPE_NUMERIC) {
								// numeric variable
								$numData = 'NaN';
							}
							else {
								// string variable
								throw new SPSSException("Error reading data: unexpected SYSMISS for string variable");
							}
							break;
						default: // 1-251 value is code minus the compression BIAS (normally always equal to 100)
							
							if ($varType==SPSSVariable::TYPE_NUMERIC) {
								// numeric variable
								$numData = $byteValue - $this->header->compressionBias;
							}
							else {
								// string variable
								throw new SPSSException("Error reading data: unexpected compression code for string variable");
							}
							break;
						}
					}
					else {
						
						/* UNCOMPRESSED DATA */
						
						if ($varType==SPSSVariable::TYPE_NUMERIC) {
							$numData = $this->readDouble();
						}
						else {
							// read a maximum of 8 characters but could be less if this is the last block
							$blockStringLength = min(8, $charactersToRead);
							// append to existing value
							$strData .= $this->readString($blockStringLength);
							// if this is the last block, skip the remaining dummy byte(s) (in block of 8 bytes)
							if ($charactersToRead < 8) {
								// $this->log("SKIP ". $this->skipBytes(8-$charactersToRead) ."/" . (8-$charactersToRead));
							}
							// update counter
							$charactersToRead -= $blockStringLength;
						}
					}
					$blocksToRead--;
				}
				
				// Store in variable
				if ($varType==SPSSVariable::TYPE_NUMERIC) {
					$var->data[] = $numData;
				}
				else {
					$strData = trim($strData);
					
					if ($var->isExtended) {
						$this->variables[$var->isExtended]->data[$i] .= $strData;
					}
					// else {
						$var->data[] = $strData;
					// }
				}
			}
		}
	}
	
	/**
	 * Read more bytes
	 * 
	 * @param int $num
	 * @param int $pos
	 * @param string $unpackFormat
	 * @return array
	 */
	private function read($num=1, $unpackFormat='C*')
	{
		if (!$this->_file) {
			return;
		}
		// @todo: stream_get_contents need php 5.2+version
		// refactory without (stream_get_contents)
		$result = stream_get_contents($this->_file, $num, $this->_cursor);
		$this->_cursor += $num;
		if ($unpackFormat) {
			$result = unpack($unpackFormat, $result);
			return array_values($result);
		}
		return $result;
	}
	
	/**
	 * Read string bytes
	 * 
	 * @param int $num
	 * @return string
	 */
	private function readString($num=1)
	{
		return $this->bytesToSring($this->read($num));
	}
	
	/**
	 * Read integer
	 * 
	 * @return integer
	 */
	private function readInt()
	{
		$bytes = $this->read(4,false);
		if ($this->isBigEndian) {
			$bytes = strrev($bytes);
		}
		$bytes = unpack("i",$bytes);
		return $bytes[1];
	}
	
	/**
	 * Reads the 8 bytes and interprets them as a double precision floating point value
	 * 
	 * @return string
	 */
	private function readDouble()
	{
		$bytes = $this->read(8,false);
		if ($this->isBigEndian) {
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
	 * Convert bytes to ASCII string
	 * 
	 * @param array $bytes
	 * @return string
	 */
	private function bytesToSring($bytes)
	{
		$str='';
		foreach($bytes as $byte) {
			$str.=chr($byte);
		}
		return $str;
	}
	
	/**
	 * Logging information
	 * 
	 * @param mixed $data
	 * @return void
	 */
	private function log($data) {
		return; // disabled
		print_r($data);
		echo PHP_EOL;
	}
}
