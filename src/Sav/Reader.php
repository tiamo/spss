<?php

namespace SPSS\Sav;

use SPSS\Buffer;
use SPSS\Utils;

class Reader
{
    /**
     * @var \SPSS\Sav\Record\Header
     */
    public $header;

    /**
     * @var \SPSS\Sav\Record\Variable[]
     */
    public $variables = [];

    /**
     * @var \SPSS\Sav\Record\ValueLabel[]
     */
    public $valueLabels = [];

    /**
     * @var array
     */
    public $documents = [];

    /**
     * @var \SPSS\Sav\Record\Info[]
     */
    public $info = [];

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var int
     */
    public $lastCase = -1;

    /**
     * @var record
     */
    public $record;

    /**
     * @var \SPSS\Buffer
     */
    protected $_buffer;

    /**
     * Reader constructor.
     *
     * @param \SPSS\Buffer $buffer
     */
    private function __construct(Buffer $buffer)
    {
        $this->_buffer = $buffer;
        $this->_buffer->context = $this;
    }

    /**
     * @param string $file
     * @return \SPSS\Sav\Reader
     */
    public static function fromFile($file)
    {
        return new self(Buffer::factory(fopen($file, 'r')));
    }

    /**
     * @param string $str
     * @return \SPSS\Sav\Reader
     */
    public static function fromString($str)
    {
        return new self(Buffer::factory($str));
    }

    /**
     * @return $this
     */
    public function readMetaData()
    {
        return $this->readHeader()->readBody();
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
        $tempVars = [];

        do {
            $recType = $this->_buffer->readInt();
            switch ($recType) {
                case Record\Variable::TYPE:
                    $variable = Record\Variable::fill($this->_buffer);
                    $tempVars[] = $variable;
                    break;
                case Record\ValueLabel::TYPE:
                    $this->valueLabels[] = Record\ValueLabel::fill($this->_buffer, [
                        // TODO: refactory
                        'variables' => $tempVars,
                    ]);
                    break;
                case Record\Info::TYPE:
                    $this->info = $infoCollection->fill($this->_buffer);
                    break;
                case Record\Document::TYPE:
                    $this->documents = Record\Document::fill($this->_buffer)->toArray();
                    break;
            }
        } while ($recType != Record\Data::TYPE);

        // Excluding the records that are creating only as a consequence of very long string records
        // from the variables computation.
        $veryLongStrings = [];
        if (isset($this->info[Record\Info\VeryLongString::SUBTYPE])) {
            $veryLongStrings = $this->info[Record\Info\VeryLongString::SUBTYPE]->toArray();
        }
        $segmentsCount = 0;
        foreach ($tempVars as $index => $var) {
            // Skip blank records from the variables computation
            if ($var->width != -1) {
                if ($segmentsCount == 0) {
                    if (isset($veryLongStrings[$var->name])) {
                        $segmentsCount = Utils::widthToSegments($veryLongStrings[$var->name]) - 1;
                    }
                    $this->variables[] = $var;
                } else {
                    $segmentsCount--;
                }
            }
        }

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

    /**
     * @return booleam
     */
    public function readCase()
    {
        if (!isset($this->record))
            $this->record = Record\Data::create();
        $this->lastCase += 1;
        if (($this->lastCase >= 0) && ($this->lastCase < $this->_buffer->context->header->casesCount)) {
            $this->record->readCase($this->_buffer, $this->lastCase);
            return true;
        }
        return false;
    }

    /**
     * @return int
     */
    public function getCaseNumber()
    {
        return $this->lastCase;
    }

    /**
     * @return int
     */
    public function getCase()
    {
        return $this->record->getRow();
    }
}
