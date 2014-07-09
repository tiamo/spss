<?php

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
	public $shortName; // < The short variable name (8 characters max)
	public $name; // < The full variable name
	public $label;
	public $missingValueFormatCode;
	public $missingValues=array();
	public $printFormatCode;
	public $writeFormatCode;
	public $valueLabels=array();
	public $extendedStringVars = array();
	public $extendedStringLength = 0;
	public $isExtended = 0;
	public $measure = -1; // < 1=nominal, 2=ordinal, 3=scale (copied from record type 7 subtype 11) */
	public $width = -1; // < display width (copied from record type 7 subtype 11) */
	public $alignment = -1; // < 0=left 1=right, 2=center (copied from record type 7 subtype 11) */
	public $data = array();
	
	public function getPrintFormat()
	{
		return self::getFormatInfo($this->getPrintFormatType());
	}
	
	public function getPrintFormatDecimals()
	{
		return ($this->printFormatCode >> 0) & 0xFF; // byte 1
	}
	
	public function getPrintFormatWidth()
	{
		return ($this->printFormatCode >> 8) & 0xFF; // byte 2
	}
	
	public function getPrintFormatType()
	{
		return ($this->printFormatCode >> 16) & 0xFF; // byte 3
	}
	
	public function getPrintFormatZero()
	{
		return ($this->printFormatCode >> 24) & 0xFF; // byte 4
	}
	
	public function getWriteFormat()
	{
		return self::getFormatInfo($this->getWriteFormatType());
	}
	
	public function getWriteFormatDecimals()
	{
		return ($this->writeFormatCode >> 0) & 0xFF; // byte 1
	}
	
	public function getWriteFormatWidth()
	{
		return ($this->writeFormatCode >> 8) & 0xFF; // byte 2
	}
	
	public function getWriteFormatType()
	{
		return ($this->writeFormatCode >> 16) & 0xFF; // byte 3
	}
	
	public function getWriteFormatZero()
	{
		return ($this->writeFormatCode >> 24) & 0xFF; // byte 4
	}
	
	/**
	 * @return A integer containing the kind of type
	 */
	public function getType()
	{
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
	public function getLabel()
	{
		return $this->label;
	}
	
	/**
	 * @return A string containing the kind of type
	 */
	public function getTypeLabel()
	{
		return $this->typeCode==0 ? 'Numeric' : 'String';
	}
	
	/**
	 * @return A string containing the kind of missing values
	 */
	public function getMissingLabel()
	{
		return $this->missingValues ? implode(', ', $this->missingValues) : 'None';
	}
	
	/**
	 * @return A string containing the kind of width
	 */
	public function getWidth()
	{
		if ($this->extendedStringLength) {
			$width = $this->extendedStringLength;
		}
		else {
			$width = $this->getWriteFormatWidth();
		}
		return $width;
	}
	
	/**
	 * Retrieves the SPSS write format number of decimals.
	 * 
	 * @return the length
	 */
	public function getDecimals()
	{
		return $this->getWriteFormatDecimals();
	}
	
	/**
	 * @return A string containing the kind of measure
	 */
	public function getAlignmentLabel()
	{
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
	public function getMeasureLabel()
	{
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
	public function getColumns()
	{
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
