<?php

namespace SPSS\Sav;

class Variable
{
    const TYPE_NUMERIC = 1;
    const TYPE_STRING = 2;
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

    public $name;

    public $width = 0;

    public $decimals = 0;

    public $format = 0;

    public $label;

    public $values = [];

    public $missing = [];

    public $columns = 8;

    public $align = 0;

    public $measure = 0;

    public $role;

    public $data = [];

    /**
     * Variable constructor.
     * @param array $data
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * SPSS represents a date as the number of seconds since the epoch, midnight, Oct. 14, 1582.
     * @param $timestamp
     * @param string $format
     * @return false|int
     */
    public static function date($timestamp, $format = 'Y M d')
    {
        return date($format, strtotime('1582-10-04 00:00:00') + $timestamp);
    }

    /**
     * This method returns the print / write format code of a variable.
     * The returned value is a tuple consisting of the format abbreviation
     * (string <= 8 chars) and a meaning (long string). Non-existent codes
     * have a (null, null) tuple returned.
     * @param integer $format
     * @return string
     */
    public static function getFormatInfo($format)
    {
        switch ($format) {
            case 0:
                return ['', 'Continuation of string variable'];
            case self::FORMAT_TYPE_A:
                return ['A', 'Alphanumeric'];
            case self::FORMAT_TYPE_AHEX:
                return ['AHEX', 'alphanumeric hexadecimal'];
            case self::FORMAT_TYPE_COMMA:
                return ['COMMA', 'F format with commas'];
            case self::FORMAT_TYPE_DOLLAR:
                return ['DOLLAR', 'Commas and floating point dollar sign'];
            case self::FORMAT_TYPE_F:
                return ['F', 'F (default numeric) format'];
            case self::FORMAT_TYPE_IB:
                return ['IB', 'Integer binary'];
            case self::FORMAT_TYPE_PIBHEX:
                return ['PIBHEX', 'Positive binary integer - hexadecimal'];
            case self::FORMAT_TYPE_P:
                return ['P', 'Packed decimal'];
            case self::FORMAT_TYPE_PIB:
                return ['PIB', 'Positive integer binary (Unsigned)'];
            case self::FORMAT_TYPE_PK:
                return ['PK', 'Positive packed decimal (Unsigned)'];
            case self::FORMAT_TYPE_RB:
                return ['RB', 'Floating point binary'];
            case self::FORMAT_TYPE_RBHEX:
                return ['RBHEX', 'Floating point binary - hexadecimal'];
            case self::FORMAT_TYPE_Z:
                return ['Z', 'Zoned decimal'];
            case self::FORMAT_TYPE_N:
                return ['N', 'N format - unsigned with leading zeros'];
            case self::FORMAT_TYPE_E:
                return ['E', 'E format - with explicit power of ten'];
            case self::FORMAT_TYPE_DATE:
                return ['DATE', 'Date format dd-mmm-yyyy'];
            case self::FORMAT_TYPE_TIME:
                return ['TIME', 'Time format hh:mm:ss.s'];
            case self::FORMAT_TYPE_DATETIME:
                return ['DATETIME', 'Date and time'];
            case self::FORMAT_TYPE_ADATE:
                return ['ADATE', 'Date in mm/dd/yyyy form'];
            case self::FORMAT_TYPE_JDATE:
                return ['JDATE', 'Julian date - yyyyddd'];
            case self::FORMAT_TYPE_DTIME:
                return ['DTIME', 'Date-time dd hh:mm:ss.s'];
            case self::FORMAT_TYPE_WKDAY:
                return ['WKDAY', 'Day of the week'];
            case self::FORMAT_TYPE_MONTH:
                return ['MONTH', 'Month'];
            case self::FORMAT_TYPE_MOYR:
                return ['MOYR', 'mmm yyyy'];
            case self::FORMAT_TYPE_QYR:
                return ['QYR', 'q Q yyyy'];
            case self::FORMAT_TYPE_WKYR:
                return ['WKYR', 'ww WK yyyy'];
            case self::FORMAT_TYPE_PCT:
                return ['PCT', 'Percent - F followed by "%"'];
            case self::FORMAT_TYPE_DOT:
                return ['DOT', 'Like COMMA, switching dot for comma'];
            case self::FORMAT_TYPE_CCA:
                return ['CCA', 'User-programmable currency format (1)'];
            case self::FORMAT_TYPE_CCB:
                return ['CCB', 'User-programmable currency format (2)'];
            case self::FORMAT_TYPE_CCC:
                return ['CCC', 'User-programmable currency format (3)'];
            case self::FORMAT_TYPE_CCD:
                return ['CCD', 'User-programmable currency format (4)'];
            case self::FORMAT_TYPE_CCE:
                return ['CCE', 'User-programmable currency format (5)'];
            case self::FORMAT_TYPE_EDATE:
                return ['EDATE', 'Date in dd.mm.yyyy style'];
            case self::FORMAT_TYPE_SDATE:
                return ['SDATE', 'Date in yyyy/mm/dd style'];
            default:
                return [null, null];
        }
    }
}