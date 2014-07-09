<?php
/**
 * SPSS Php Reader
 * 
 * @package fom.spss
 * @author vk.tiamo@gmail.com
 * @dependence php5.2+ version
 */

require_once 'SPSSVariable.php';

class SPSSException extends Exception {}

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
					$this->log('#'.$recordType);
					break;
				
				// Value and labels
				case 3:
					$this->_readValueLabels();
					$this->log('#'.$recordType);
					break;
				
				// Read and parse document records
				case 6:
					$this->documents = $this->_readDocuments();
					$this->log('#'.$recordType);
					break;
				
				// Read and parse additional records
				case 7:
					$subtype = $this->readInt();
					$size = $this->readInt();
					$count = $this->readInt();
					$datalen = $size * $count;
					
					$this->log('#'.$recordType . '--' . $subtype . ' ('.$size.','.$count.')<br/>');
					
					switch($subtype) {
						
						// SPSS Record Type 7 Subtype 3 - Source system characteristics
						case 3:
							$this->specificInfo = $this->_readSpecificInfo();
							break;
						
						// SPSS Record Type 7 Subtype 4 - Source system floating pt constants 
						case 4:
							$this->specificFloatInfo = $this->_readSpecificFloatInfo();
							break;
						
						// SPSS Record Type 7 Subtype 5 - Variable sets
						case 5:
							$this->variableSets = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 6 - Trends date information
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
						
						// SPSS Record Type 7 Subtype 7 - Multi response groups
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
						
						// SPSS Record Type 7 Subtype 19 -  Extended multiple response groups
						case 19:
							$this->extendedGroups = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 20 -  Encoding, aka code page
						case 20:
							$this->charset = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 21 - Extended value labels
						case 21:
							$this->extendedValueLabels = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 22 - Missing values for long strings
						case 22:
							$this->sortIndex = $this->readString($datalen);
							break;
						
						// SPSS Record Type 7 Subtype 23 - Sort Index information
						case 23:
							$this->sortIndex = $this->readString($datalen);
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
			$hasLabel = $this->readInt();
			// read missing value format code
			$var->missingValueFormatCode = $this->readInt();
			if (abs($var->missingValueFormatCode) > 3) {
				throw new SPSSException("Error reading variable Record: invalid missing value format code [" . $var->missingValueFormatCode . "]. Range is -3 to 3.");
			}
			// read print format code
			$var->printFormatCode = $this->readInt();
			// read write format code
			$var->writeFormatCode = $this->readInt();
			// read varname
			$var->shortName = $var->name = trim($this->readString(8)); // 8-byte variable name
			// read label length and label only if a label exists
			if ($hasLabel==1) {
				$labelLength = $this->readInt();
				$var->label = $this->readString($labelLength);
				// variableRecord labels are stored in chunks of 4-bytes
				// --> we need to skip unused bytes in the last chunk
				if ($labelLength % 4 != 0) {
					$this->skipBytes(4 - ($labelLength % 4));
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
