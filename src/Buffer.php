<?php

namespace SPSS;

class Buffer
{
    /**
     * @var mixed
     */
    public $context;

    /**
     * @var bool
     */
    public $isBigEndian = false;

    /**
     * @var string
     */
    public $charset;

    /**
     * @var resource
     */
    private $_stream;

    /**
     * @var int
     */
    private $_position = 0;

    /**
     * Buffer constructor.
     * @param null|resource $resource
     */
    public function __construct($resource = null)
    {
        if (is_null($resource)) {
            $this->_stream = fopen('php://memory', 'r+');
        } else {
            $this->_stream = $resource;
        }
    }

    /**
     * @param $file
     * @return int|false
     */
    public function saveToFile($file)
    {
        rewind($this->_stream);
        return file_put_contents($file, $this->_stream);
    }

    /**
     * @param string $file
     * @return Buffer
     * @throws Exception
     */
    public static function fromFile($file)
    {
        if (!file_exists($file)) {
            throw new Exception(sprintf('File "%s" not found.', $file));
        }
        return new self(fopen($file, 'r'));
    }

    /**
     * @param string $data
     * @return Buffer
     */
    public static function fromString($data)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $data);
        return new self($stream);
    }

    /**
     * @param int $length
     * @return Buffer
     * @throws Exception
     */
    public function allocate($length)
    {
        $stream = fopen('php://memory', 'r+');
        if (stream_copy_to_stream($this->_stream, $stream, $length)) {
            $this->skip($length);
            return new self($stream);
        }
        throw new Exception('Failed to allocate buffer');
    }

    /**
     * @param int $length
     * @return false|string
     */
    public function read($length)
    {
        $bytes = stream_get_contents($this->_stream, $length, $this->_position);
        if ($bytes !== false) {
            $this->_position += $length;
        }
        return $bytes;
    }

    /**
     * @param string $data
     * @param null|int $length
     * @return int
     */
    public function write($data, $length = null)
    {
        if ($length) {
            return fwrite($this->_stream, $data, $length);
        }
        return fwrite($this->_stream, $data);
    }

    /**
     * @param int $length
     * @param int $round
     * @param null $charset
     * @return false|string
     */
    public function readString($length, $round = 0, $charset = null)
    {
        $bytes = $this->read($length);
        if ($bytes != false) {
            if ($round) {
                $this->skip(self::roundUp($length, $round) - $length);
            }
            $str = self::bytesToString(unpack('C*', $bytes));
            if ($charset) {
                $str = iconv($charset, 'utf8', $str);
            } elseif ($this->charset) {
                $str = iconv($this->charset, 'utf8', $str);
            }
            return $str;
        }
        return false;
    }

    /**
     * @param $data
     * @param int|string $length
     * @param null $charset
     */
    public function writeString($data, $length = '*', $charset = null)
    {
        if ($charset) {
            $data = iconv('utf8', $charset, $data);
        } elseif ($this->charset) {
            $data = iconv('utf8', $this->charset, $data);
        }
        $this->write(pack('A' . $length, $data));
    }

    /**
     * @param int $length
     * @param string $format
     * @return mixed
     */
    public function readNumeric($length, $format)
    {
        $bytes = $this->read($length);
        if ($bytes != false) {
            if ($this->isBigEndian) {
                $bytes = strrev($bytes);
            }
            $data = unpack($format, $bytes);
            return $data[1];
        }
        return false;
    }

    /**
     * @param $data
     * @param $format
     * @param null $length
     */
    public function writeNumeric($data, $format, $length = null)
    {
        $this->write(pack($format, $data), $length);
    }

    /**
     * @return double
     */
    public function readDouble()
    {
        return $this->readNumeric(8, 'd');
    }

    /**
     * @param $data
     */
    public function writeDouble($data)
    {
        $this->writeNumeric($data, 'd', 8);
    }

    /**
     * @return float
     */
    public function readFloat()
    {
        return $this->readNumeric(4, 'f');
    }

    /**
     * @param $data
     */
    public function writeFloat($data)
    {
        $this->writeNumeric($data, 'f', 4);
    }

    /**
     * @return int
     */
    public function readInt()
    {
        return $this->readNumeric(4, 'i');
    }

    /**
     * @param $data
     */
    public function writeInt($data)
    {
        $this->writeNumeric($data, 'i', 4);
    }

    /**
     * @return int
     */
    public function readShort()
    {
        return $this->readNumeric(2, 'v');
    }

    /**
     * @param $data
     */
    public function writeShort($data)
    {
        $this->writeNumeric($data, 'v', 2);
    }

    /**
     * @param $length
     */
    public function writeNull($length)
    {
        $this->write(pack('x' . $length));
    }

    /**
     * @return int
     */
    public function position()
    {
        return $this->_position;
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * @param int $length
     * @return void
     */
    public function skip($length)
    {
        $this->_position += $length;
    }

    /**
     * @param array $bytes
     * @return string
     */
    public static function bytesToString(array $bytes)
    {
        $str = '';
        foreach ($bytes as $byte) $str .= chr($byte);
        return $str;
    }

    /**
     * @param array $bytes
     * @return int
     */
    public static function bytesToInt(array $bytes)
    {
        return $bytes[3] << 24 | $bytes[2] << 16 | $bytes[1] << 8 | $bytes[0];
    }

    /**
     * @param $int
     * @return array
     */
    public static function intToBytes($int)
    {
        return [
            0xFF & $int,
            0xFF & $int >> 8,
            0xFF & $int >> 16,
            0xFF & $int >> 24,
        ];
    }

    /**
     * Rounds X up to the next multiple of Y.
     * @param int $x
     * @param int $y
     * @return int
     */
    public static function roundUp($x, $y)
    {
        return ceil($x / $y) * $y;
    }

    /**
     * Rounds X down to the prev multiple of Y.
     * @param int $x
     * @param int $y
     * @return int
     */
    public static function roundDown($x, $y)
    {
        return floor($x / $y) * $y;
    }
}