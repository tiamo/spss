<?php

namespace SPSS\Sav;

class Variable
{
    // const TYPE_NUMERIC = 1;
    // const TYPE_STRING = 2;

    const FORMAT_TYPE_A = 1;
    const FORMAT_TYPE_AHEX = 2;
    const FORMAT_TYPE_COMMA = 3;
    const FORMAT_TYPE_DOLLAR = 4;
    const FORMAT_TYPE_F = 5;
    const FORMAT_TYPE_IB = 6;
    const FORMAT_TYPE_PIBHEX = 7;
    const FORMAT_TYPE_P = 8;
    const FORMAT_TYPE_PIB = 9;
    const FORMAT_TYPE_PK = 10;
    const FORMAT_TYPE_RB = 11;
    const FORMAT_TYPE_RBHEX = 12;
    const FORMAT_TYPE_Z = 15;
    const FORMAT_TYPE_N = 16;
    const FORMAT_TYPE_E = 17;
    const FORMAT_TYPE_DATE = 20;
    const FORMAT_TYPE_TIME = 21;
    const FORMAT_TYPE_DATETIME = 22;
    const FORMAT_TYPE_ADATE = 23;
    const FORMAT_TYPE_JDATE = 24;
    const FORMAT_TYPE_DTIME = 25;
    const FORMAT_TYPE_WKDAY = 26;
    const FORMAT_TYPE_MONTH = 27;
    const FORMAT_TYPE_MOYR = 28;
    const FORMAT_TYPE_QYR = 29;
    const FORMAT_TYPE_WKYR = 30;
    const FORMAT_TYPE_PCT = 31;
    const FORMAT_TYPE_DOT = 32;
    const FORMAT_TYPE_CCA = 33;
    const FORMAT_TYPE_CCB = 34;
    const FORMAT_TYPE_CCC = 35;
    const FORMAT_TYPE_CCD = 36;
    const FORMAT_TYPE_CCE = 37;
    const FORMAT_TYPE_EDATE = 38;
    const FORMAT_TYPE_SDATE = 39;

    const ALIGN_LEFT = 0;
    const ALIGN_RIGHT = 1;
    const ALIGN_CENTER = 2;

    const MEASURE_UNKNOWN = 0;
    const MEASURE_NOMINAL = 1;
    const MEASURE_ORDINAL = 2;
    const MEASURE_SCALE = 3;

    const ROLE_INPUT = 0;
    const ROLE_TARGET = 1;
    const ROLE_BOTH = 2;
    const ROLE_NONE = 3;
    const ROLE_PARTITION = 4;
    const ROLE_SPLIT = 5;

    public $name;
    public $width = 8;
    public $decimals = 0;
    public $format = 0;
    public $columns;
    public $alignment;
    public $measure;
    public $role;
    public $label;
    public $values = array();
    public $missing = array();

    /**
     * @var array
     */
    public $attributes = array(
        // '$@Role' => self::ROLE_BOTH
    );

    /**
     * @var array
     */
    public $data = array();

    /**
     * Variable constructor.
     *
     * @param  array  $data
     */
    public function __construct($data = array())
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @param  int  $format
     *
     * @return bool
     */
    public static function isNumberFormat($format)
    {
        return \in_array($format, array(
            self::FORMAT_TYPE_COMMA,
            self::FORMAT_TYPE_F,
            self::FORMAT_TYPE_DATETIME,
            self::FORMAT_TYPE_DATE,
            self::FORMAT_TYPE_TIME,
        ), true);
    }

    /**
     * This method returns the print / write format code of a variable.
     * The returned value is a tuple consisting of the format abbreviation
     * (string <= 8 chars) and a meaning (long string).
     * Non-existent codes have a (null, null) tuple returned.
     *
     * @param  int  $format
     *
     * @return array
     */
    public static function getFormatInfo($format)
    {
        switch ($format) {
            case 0:
                return array('', 'Continuation of string variable');
            case self::FORMAT_TYPE_A:
                return array('A', 'Alphanumeric');
            case self::FORMAT_TYPE_AHEX:
                return array('AHEX', 'alphanumeric hexadecimal');
            case self::FORMAT_TYPE_COMMA:
                return array('COMMA', 'F format with commas');
            case self::FORMAT_TYPE_DOLLAR:
                return array('DOLLAR', 'Commas and floating point dollar sign');
            case self::FORMAT_TYPE_F:
                return array('F', 'F (default numeric) format');
            case self::FORMAT_TYPE_IB:
                return array('IB', 'Integer binary');
            case self::FORMAT_TYPE_PIBHEX:
                return array('PIBHEX', 'Positive binary integer - hexadecimal');
            case self::FORMAT_TYPE_P:
                return array('P', 'Packed decimal');
            case self::FORMAT_TYPE_PIB:
                return array('PIB', 'Positive integer binary (Unsigned)');
            case self::FORMAT_TYPE_PK:
                return array('PK', 'Positive packed decimal (Unsigned)');
            case self::FORMAT_TYPE_RB:
                return array('RB', 'Floating point binary');
            case self::FORMAT_TYPE_RBHEX:
                return array('RBHEX', 'Floating point binary - hexadecimal');
            case self::FORMAT_TYPE_Z:
                return array('Z', 'Zoned decimal');
            case self::FORMAT_TYPE_N:
                return array('N', 'N format - unsigned with leading zeros');
            case self::FORMAT_TYPE_E:
                return array('E', 'E format - with explicit power of ten');
            case self::FORMAT_TYPE_DATE:
                return array('DATE', 'Date format dd-mmm-yyyy');
            case self::FORMAT_TYPE_TIME:
                return array('TIME', 'Time format hh:mm:ss.s');
            case self::FORMAT_TYPE_DATETIME:
                return array('DATETIME', 'Date and time');
            case self::FORMAT_TYPE_ADATE:
                return array('ADATE', 'Date in mm/dd/yyyy form');
            case self::FORMAT_TYPE_JDATE:
                return array('JDATE', 'Julian date - yyyyddd');
            case self::FORMAT_TYPE_DTIME:
                return array('DTIME', 'Date-time dd hh:mm:ss.s');
            case self::FORMAT_TYPE_WKDAY:
                return array('WKDAY', 'Day of the week');
            case self::FORMAT_TYPE_MONTH:
                return array('MONTH', 'Month');
            case self::FORMAT_TYPE_MOYR:
                return array('MOYR', 'mmm yyyy');
            case self::FORMAT_TYPE_QYR:
                return array('QYR', 'q Q yyyy');
            case self::FORMAT_TYPE_WKYR:
                return array('WKYR', 'ww WK yyyy');
            case self::FORMAT_TYPE_PCT:
                return array('PCT', 'Percent - F followed by "%"');
            case self::FORMAT_TYPE_DOT:
                return array('DOT', 'Like COMMA, switching dot for comma');
            case self::FORMAT_TYPE_CCA:
                return array('CCA', 'User-programmable currency format (1)');
            case self::FORMAT_TYPE_CCB:
                return array('CCB', 'User-programmable currency format (2)');
            case self::FORMAT_TYPE_CCC:
                return array('CCC', 'User-programmable currency format (3)');
            case self::FORMAT_TYPE_CCD:
                return array('CCD', 'User-programmable currency format (4)');
            case self::FORMAT_TYPE_CCE:
                return array('CCE', 'User-programmable currency format (5)');
            case self::FORMAT_TYPE_EDATE:
                return array('EDATE', 'Date in dd.mm.yyyy style');
            case self::FORMAT_TYPE_SDATE:
                return array('SDATE', 'Date in yyyy/mm/dd style');
        }

        return array(null, null);
    }

    /**
     * @param  int  $alignment
     *
     * @return string
     */
    public static function alignmentToString($alignment)
    {
        switch ($alignment) {
            case self::ALIGN_LEFT:
                return 'Left';
            case self::ALIGN_RIGHT:
                return 'Right';
            case self::ALIGN_CENTER:
                return 'Center';
        }

        return 'Invalid';
    }

    /**
     * @return int
     */
    public function getMeasure()
    {
        if (null !== $this->measure) {
            return $this->measure;
        }

        return 0 === $this->width ? self::MEASURE_UNKNOWN : self::MEASURE_NOMINAL;
    }

    /**
     * @return int
     */
    public function getAlignment()
    {
        if (null !== $this->alignment) {
            return $this->alignment;
        }

        return 0 === $this->width ? self::ALIGN_RIGHT : self::ALIGN_LEFT;
    }

    /**
     * @return int
     */
    public function getColumns()
    {
        if (null !== $this->columns) {
            return $this->columns;
        }

        return 8;
    }
}
