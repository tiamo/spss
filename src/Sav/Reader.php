<?php

namespace SPSS\Sav;

use SPSS\Buffer;
use SPSS\Exception;
use SPSS\Sav\Record\Data;
use SPSS\Sav\Record\Document;
use SPSS\Sav\Record\Header;
use SPSS\Sav\Record\Info;
use SPSS\Sav\Record\ValueLabel;
use SPSS\Sav\Record\Variable;

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
     * @var array Data matrix
     */
    public $data = [];

    /**
     * Reader constructor.
     * @param Buffer $buffer
     * @throws Exception
     */
    private function __construct(Buffer $buffer)
    {
        $buffer->context = $this;
        $this->header = Header::fill($buffer);

        do {
            $recType = $buffer->readInt();
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
                    switch ($subtype) {
                        case Info\MachineInteger::SUBTYPE:
                            $this->info[$subtype] = Info\MachineInteger::fill($buffer);
                            break;
                        case Info\MachineFloatingPoint::SUBTYPE:
                            $this->info[$subtype] = Info\MachineFloatingPoint::fill($buffer);
                            break;
                        case Info\VariableDisplayParam::SUBTYPE:
                            $this->info[$subtype] = Info\VariableDisplayParam::fill($buffer);
                            break;
                        case Info\LongVariableNames::SUBTYPE:
                            $this->info[$subtype] = Info\LongVariableNames::fill($buffer);
                            break;
                        case Info\VeryLongString::SUBTYPE:
                            $this->info[$subtype] = Info\VeryLongString::fill($buffer);
                            break;
                        case Info\ExtendedNumberOfCases::SUBTYPE:
                            $this->info[$subtype] = Info\ExtendedNumberOfCases::fill($buffer);
                            break;
                        case Info\VariableAttributes::SUBTYPE:
                            $this->info[$subtype] = Info\VariableAttributes::fill($buffer);
                            break;
                        case Info\VariableRoles::SUBTYPE:
                            $this->info[$subtype] = Info\VariableRoles::fill($buffer);
                            break;
                        case Info\CharacterEncoding::SUBTYPE:
                            $this->info[$subtype] = Info\CharacterEncoding::fill($buffer);
                            break;
                        case Info\LongStringValueLabels::SUBTYPE:
                            $this->info[$subtype] = Info\LongStringValueLabels::fill($buffer);
                            break;
                        case Info\LongStringMissingValues::SUBTYPE:
                            $this->info[$subtype] = Info\LongStringMissingValues::fill($buffer);
                            break;
                        default:
                            $this->info[$subtype] = Info\Unknown::fill($buffer);
                    }
                    break;
            }
        } while ($recType != Data::TYPE);

        $this->data = Data::fill($buffer)->data;
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