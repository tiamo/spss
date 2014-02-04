<?php

defined('BIG_ENDIAN') OR define('BIG_ENDIAN', pack('L', 1) === pack('N', 1));

class SPSS_Exception extends Exception {}

class SPSS
{
	const RECORD_TYPE_1 = 0x324c4624;
	const RECORD_TYPE_2 = 2;
	const RECORD_TYPE_3 = 3;
	const RECORD_TYPE_4 = 4;
	const RECORD_TYPE_6 = 6;
	const RECORD_TYPE_7 = 7;
	const RECORD_TYPE_END = 999;
	
	public $header;
	
	private $_file;
	private $_buffer;
	private $_cursor=0;
	
	public function __construct($file=null)
	{
		if ($file)
		{
			if (!@file_exists($file))
				throw new SPSS_Exception(sprintf('File "%s" not exists', $file));
			$this->_file = fopen($file,'r');
		}
	}
	
	public function __destruct()
	{
		if ($this->_file) fclose($this->_file);
	}
	
	/**
	 * Parse SPSS file
	 */
	public static function parse($file)
	{
		$spss = new self($file);
		return $spss->_read();
	}

	/**
	 * Read all other records in the dictionary
	 * @return array
	 */
	private function _read()
	{
		$result = new stdClass();
		$result->variables = array();
		$result->valueLabels = array();
		$result->valueLabelsIndex = array();
		
		$this->_buffer = '';
		$this->_cursor = 0;
		$stop = false;
		
		while (!$stop)
		{
			$type = $this->readInt();
			switch($type)
			{
				// General Inforamtion
				case(self::RECORD_TYPE_1):
					$this->header = $this->_readHeader();
					break;
				
				// Variable Record
				case(self::RECORD_TYPE_2):
					$result->variables[] = $this->_readVariable();
					break;
				
				// Value and labels
				case(self::RECORD_TYPE_3):
					$result->valueLabels[] = $this->_readValueLabels();
					break;
				
				// Read and parse value label index records
				case(self::RECORD_TYPE_4):
					$count = $this->readInt();
					$data = array();
					for($i=0;$i<$count;$i++) $data[] = $this->readInt()-1; // TODO: Чтобы совпадали индексы
					$result->valueLabelsIndex[] = $data;
					break;
				
				// Read and parse document records
				case(self::RECORD_TYPE_6):
					$result->documents = $this->_readDocuments();
					break;
				
				// Read and parse additional records
				case(self::RECORD_TYPE_7):
					$subtype = $this->readInt();
					$datalen = $this->readInt();
					$count = $this->readInt();
					switch($subtype)
					{
						# SpecificInfoReader
						case 3:
							$result->specificInfo = $this->_readSpecificInfo();
							break;
						# SpecificFloatInfoReader
						case 4:
							$result->specificFloatInfo = $this->_readSpecificFloatInfo();
							break;
						# VariableSetsReader
						case 5:
							$result->variableSets = $this->readString($datalen * $count);
							break;
						# MultiResponseReader
						case 7:
							// TODO: parse
							$result->multiResponse = $this->readString($datalen * $count);
							break;
						# VariableParamsReader
						case 11:
							$result->variableParams = $this->readString($datalen * $count);
							break;
						# ExtendedNamesReader
						case 13:
							// TODO: parse
							$result->extendedNames = $this->readString($datalen * $count);
							break;
						# ExtendedStringsReader
						case 14:
							// TODO: parse
							$result->extendedStrings = $this->readString($datalen * $count);
							break;
						# NumberOfCasesReader
						case 16:
							$data = new stdClass();
							$data->byteOrder = $this->readInt();
							$data->count = $this->readInt();
							$result->numberOfCases = $data;
							break;
						# DatasetAttributesReader
						case 17:
							$result->datasetAttributes = $this->readString($datalen * $count);
							break;
						# VariableAttributesReader
						case 18:
							// TODO: parse
							$result->variableAttributes = $this->readString($datalen * $count);
							break;
						# Charset
						case 20:
							$result->charset = $this->readString($datalen * $count);
							break;
						# XML info
						case 24:
							$result->xmlInfo = $this->readString($datalen * $count);
							break;
						default:
							// $this->skipBytes($datalen * $count);
							throw new SPSS_Exception(sprintf("Can't instantiate additional reader for %s subtype.",$subtype));
							break;
					}
					break;
					
				// Finish
				case(self::RECORD_TYPE_END):
					$this->readInt();
					$stop = true;
					break;
			}
		}
		
		return $result;
	}
	
	/**
	 * Read and parse general information
	 * @return object
	 */
	private function _readHeader()
	{
		$data = new stdClass();
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
	 * @return object
	 */
	private function _readVariable()
	{
		$data = new stdClass();
		$data->typeCode = $this->readInt();
		$data->labelFlag = $this->readInt();
		$data->missingValueFormat = $this->readInt();
		$data->printFormatCode = $this->readInt();
		$data->writeFormatCode = $this->readInt();
		$data->name = $this->readString(8);
		if ($data->labelFlag==1)
		{
			if ($this->header->layoutCode==1)
			{
				 $data->label = $this->readString(40);
			}
			else
			{
				// round label len up to nearest multiple of 4 bytes
				$tmp_len = $this->readInt();
				$tmp_mod = ($tmp_len % 4);
				if ($tmp_mod != 0) {
					$tmp_len += 4 - $tmp_mod;
				}
				$data->label = $this->readString($tmp_len);
			}
			$data->label = trim($data->label);
		}
		if ($data->missingValueFormat!=0)
		{
			$data->missingValues = array();
			for($i=0;$i<abs($data->missingValueFormat);$i++)
			{
				$data->missingValues[] = $this->readDouble();
			}
		}
		return $data;
	}
	
	/**
	 * Read valuelabels information
	 * @return object
	 */
	private function _readValueLabels()
	{
		$data = array();
		$count = $this->readInt();
		
		// do for each pair
		for($i=0; $i < $count; $i++)
		{
			$value = $this->readDouble();
			if ($this->header->layoutCode == 1)
			{
				$label = $this->readString(20);
				$this->readInt();
			}
			else
			{
				$tmp_len = reset($this->read());
				if ($tmp_len < 1 || $tmp_len > 255)
					throw new SPSS_Exception(sprintf('Invalid value label record "%s"',$tmp_len));
				# round label len up to nearest multiple of 8 bytes
				$tmp_mod = (($tmp_len + 1) % 8);
				if ($tmp_mod != 0) {
					$tmp_len += 8 - $tmp_mod;
				}
				$label = $this->readString($tmp_len);
			}
			
			$data[$value] = trim($label);
		}
		
		return $data;
	}
	
	/**
	 * Read documents
	 * @return object
	 */
	private function _readDocuments()
	{
		$data = array();
		$count = $this->readInt();
		for ($i=0; $i < $count; $i++)
		{
			$data[] = $this->readString(80);
		}
		return $data;
	}
	
	/**
	 * Read Specific information
	 * @return object
	 */
	private function _readSpecificInfo()
	{
		$data = new stdClass();
		$data->releaseNumber = $this->readInt();
		$data->releaseSubNumber = $this->readInt();
		$data->releaseIdentifier = $this->readInt();
		$data->machineCode = $this->readInt();
		$data->floatingPointCode = $this->readInt();
		$data->compressionSchemeCode = $this->readInt();
		$data->endianCode = $this->readInt();
		$data->charRepresentationCode = $this->readInt();
		return $data;
	}
	
	/**
	 * Read Specific float information
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
	 * @param int $num
	 * @param int $pos
	 * @return array
	 */
	private function read($num=1, $pos=-1, $unpackFormat='C*')
	{
		if (!$this->_file)
			return;
		if ($pos >= 0)
			$this->_cursor = $pos;
		// do we need more data?		
		if ($this->_cursor + $num-1 >= strlen($this->_buffer))
		{
			$response = null;
			$end = ($this->_cursor + $num);
			while (strlen($this->_buffer) < $end && $response !== false)
			{
				$need = $end - ftell($this->_file);
				if ($response = fread($this->_file, $need))
					$this->_buffer .= $response;
				else
					throw new SPSS_Exception('Unexpected termination');
			}
		}
		$result = substr($this->_buffer, $this->_cursor, $num);
		$this->_cursor += $num;
		if ($unpackFormat)
		{
			// we are dealing with bytes here, so force the encoding
			// $result = mb_convert_encoding($result, "8BIT");
			$result = unpack($unpackFormat, $result);
			return array_values($result);
		}
		return $result;
	}
	
	/**
	 * Read string bytes
	 * @param int $num
	 * @param int $pos
	 * @return string
	 */
	private function readString($num=1)
	{
		return $this->showBytes($this->read($num));
	}
	
	/**
	 * Read integer
	 * @ignore
	 */
	private function readInt()
	{
		$bytes = $this->read(4);
        return ($bytes[3] & 0xff) << 24 | ($bytes[2] & 0xff) << 16 | ($bytes[1] & 0xff) << 8 | $bytes[0] & 0xff;
    }
	
	/**
	 * Read big integer
	 * @ignore
	 */
    private function readBigInt()
	{
		$bytes = $this->read(4);
        return ($bytes[0] & 0xff) << 24 | ($bytes[1] & 0xff) << 16 | ($bytes[2] & 0xff) << 8 | $bytes[3] & 0xff;
    }
	
	/**
	 * Read Short
	 * @ignore
	 */
    private function readShort()
	{
		$bytes = $this->read(2);
        return ($bytes[0] & 0xff) | ($bytes[1] & 0xff) << 8;
    }
	
	/**
	 * Read Big Short
	 * @ignore
	 */
    private function readBigShort($bytes=null)
	{
		$bytes = $this->read(2);
        return ($bytes[0] & 0xff) << 8 | ($bytes[1] & 0xff);
    }
	
	/**
	 * Reads the 8 bytes and interprets them as a double precision floating point value
	 * @return string
	 */
	private function readDouble()
	{
		$double = $this->readString(8,-1,false);
		if (BIG_ENDIAN) $double = strrev($double);
		$double = unpack("d",$double);
		return $double[1];
	}
	
	/**
	 * Skip bytes
	 * @param int $num
	 * @return void
	 */
    private function skipBytes($num)
	{
		$this->_cursor += (int) $num;
    }
	
	/**
	 * Show bytes
	 * @param array $bytes
	 * @return string
	 */
	private function showBytes($bytes)
	{
		$str='';
		foreach($bytes as $byte)
			$str.=chr($byte);
		return $str;
	}
}
