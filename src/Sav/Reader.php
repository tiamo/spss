<?php

namespace SPSS\Sav;

use SPSS\Buffer;
use SPSS\Sav\Record\Document;
use SPSS\Sav\Record\Header;
use SPSS\Sav\Record\Info;
use SPSS\Sav\Record\ValueLabel;
use SPSS\Sav\Record\Variable;

class Reader
{
    /**
     * @var Buffer
     */
    protected $buffer;

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
     * Reader constructor.
     * @param Buffer $buffer
     */
    private function __construct(Buffer $buffer)
    {
        $this->header = Header::fill($buffer);

        do {
            $recType = $this->buffer->readInt();
            switch ($recType) {
                case Variable::TYPE:
                    $this->variables[] = Variable::fill($buffer);
                    break;
                case ValueLabel::TYPE:
                    $this->valueLabels[] = ValueLabel::fill($buffer);
                    break;
                case Document::TYPE:
                    $this->documents = Document::fill($buffer)->lines;
                    break;
                case Info::TYPE:

                    $subtype = $buffer->readInt();

                    print_r($this);
                    exit;

                    break;
                default:
                    break;
            }
        } while ($recType != 999);

        // TODO: read data matrix

        print_r($this);
    }

    /**
     * @param string $file
     * @return Reader
     */
    public static function fromFile($file)
    {
        return new self(Buffer::fromFile($file));
    }

    /**
     * @param string $str
     * @return Reader
     */
    public static function fromString($str)
    {
        return new self(Buffer::fromString($str));
    }
}