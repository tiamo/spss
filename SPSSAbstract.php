<?php

/**
 * Class SPSSAbstract
 */
abstract class SPSSAbstract
{
	const RECORD_TYPE_VARIABLE = 2;
	const RECORD_TYPE_VALUE_LABELS = 3;
	const RECORD_TYPE_VALUE_LABELS_INDEX = 4;
	const RECORD_TYPE_DOCUMENTS = 6;
	const RECORD_TYPE_ADDITIONAL = 7;
	const RECORD_TYPE_FINAL = 999;

	const COMPRESS_SKIP_CODE = 0;
    const COMPRESS_END_OF_FILE = 252;
    const COMPRESS_NOT_COMPRESSED = 253;
	const COMPRESS_ALL_BLANKS = 254;
    const COMPRESS_MISSING_VALUE = 255;

	/**
	 * Number of bytes really stored in each segment of a very long string variable.
	 */
	const REAL_VLS_CHUNK = 255;

	/**
	 * Returns true if WIDTH is a very long string width, false otherwise.
	 * 
	 * @param integer $width
	 * @return boolean
	 */
	public static function isVeryLong($width)
	{
		return $width>self::REAL_VLS_CHUNK;
	}

	/**
	 * Convert bytes array to integer
	 * 
	 * @params array $bytes
	 * @return integer
	 */
	public static function bytesToInt(array $bytes)
	{
		return $bytes[3]<<24 | $bytes[2]<<16 | $bytes[1]<<8 | $bytes[0]<<0;
	}

	/**
	 * Rounds X up to the next multiple of Y.
	 * 
	 * @params integer $x
	 * @params integer $y
	 * @return integer
	 */
	public static function roundUp($x, $y = 8)
	{
		return $x - ($x%$y) + $y;
	}
}

/**
 * Class SPSSException
 */
class SPSSException extends Exception
{
}
