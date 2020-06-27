<?php

namespace SPSS\Sav;

use SPSS\Buffer;
use SPSS\Sav\Record\Header;
use SPSS\Sav\Record\Info;
use SPSS\Sav\Record\Variable;
use SPSS\Sav\Record\ValueLabel;

class Reader
{
    /**
     * @var Header
     */
    public $header;

    /**
     * @var Variable[]
     */
    public $variables = [];

    /**
     * @var ValueLabel[]
     */
    public $valueLabels = [];

    /**
     * @var array
     */
    public $documents = [];

    /**
     * @var Info[]
     */
    public $info = [];

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var Buffer
     */
    protected $_buffer;

    /**
     * Reader constructor.
     *
     * @param  Buffer  $buffer
     */
    private function __construct(Buffer $buffer)
    {
        $this->_buffer = $buffer;
        $this->_buffer->context = $this;
    }

    /**
     * @param  string  $file
     * @return Reader
     */
    public static function fromFile($file)
    {
        return new self(Buffer::factory(fopen($file, 'rb')));
    }

    /**
     * @param  string  $str
     * @return Reader
     */
    public static function fromString($str)
    {
        return new self(Buffer::factory($str));
    }

    /**
     * @return $this
     */
    public function read()
    {
        return $this->readHeader()->readBody()->readData();
    }

    /**
     * @return $this
     */
    public function readHeader()
    {
        $this->header = Record\Header::fill($this->_buffer);

        return $this;
    }

    /**
     * @return $this
     */
    public function readBody()
    {
        if (! $this->header) {
            $this->readHeader();
        }

        // TODO: refactory
        $infoCollection = new Record\InfoCollection();

        do {
            $recType = $this->_buffer->readInt();
            switch ($recType) {
                case Record\Variable::TYPE:
                    $this->variables[] = Record\Variable::fill($this->_buffer);
                    break;
                case Record\ValueLabel::TYPE:
                    $this->valueLabels[] = Record\ValueLabel::fill($this->_buffer, [
                        // TODO: refactory
                        'variables' => $this->variables,
                    ]);
                    break;
                case Record\Info::TYPE:
                    $this->info = $infoCollection->fill($this->_buffer);
                    break;
                case Record\Document::TYPE:
                    $this->documents = Record\Document::fill($this->_buffer)->toArray();
                    break;
            }
        } while ($recType !== Record\Data::TYPE);

        return $this;
    }

    /**
     * @return $this
     */
    public function readData()
    {
        $this->data = Record\Data::fill($this->_buffer)->toArray();

        return $this;
    }
}
