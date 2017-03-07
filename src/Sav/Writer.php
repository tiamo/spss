<?php

namespace SPSS\Sav;

use SPSS\Buffer;

class Writer
{
    /**
     * @var Record\Header
     */
    public $header;

    /**
     * @var Record\Variable[]
     */
    public $variables = [];

    /**
     * @var Record\ValueLabel[]
     */
    public $valueLabels = [];

    /**
     * @var Record\Document
     */
    public $document;

    /**
     * @var Record\Info[]
     */
    public $info = [];

    /**
     * @var Record\Data
     */
    public $data;

    /**
     * @var Buffer
     */
    protected $buffer;

    /**
     * Writer constructor.
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->buffer = Buffer::factory();
        $this->buffer->context = $this;

        if (!empty($data)) {
            $this->init($data);
        }
    }

    /**
     * @param array $data
     */
    public function init($data)
    {
        $this->header = new Record\Header($data['header']);
        $this->header->nominalCaseSize = 0;
        $this->header->casesCount = 0;

        $this->document = new Record\Document();

        $this->info[Record\Info\MachineInteger::SUBTYPE] = new Record\Info\MachineInteger();
        $this->info[Record\Info\MachineFloatingPoint::SUBTYPE] = new Record\Info\MachineFloatingPoint();
        $this->info[Record\Info\VariableDisplayParam::SUBTYPE] = new Record\Info\VariableDisplayParam();
        $this->info[Record\Info\LongVariableNames::SUBTYPE] = new Record\Info\LongVariableNames();
        $this->info[Record\Info\VeryLongString::SUBTYPE] = new Record\Info\VeryLongString();
        $this->info[Record\Info\LongStringValueLabels::SUBTYPE] = new Record\Info\LongStringValueLabels();
        $this->info[Record\Info\LongStringMissingValues::SUBTYPE] = new Record\Info\LongStringMissingValues();

        $this->data = new Record\Data();

        /** @var Variable $var */
        foreach ($data['variables'] as $idx => $var) {

            if (is_array($var)) {
                $var = new Variable($var);
            }

            $shortName = strtoupper(substr($var->name, 0, 8));

            $variable = new Record\Variable();
            $variable->name = $shortName;
            $variable->width = $var->width;
            $variable->label = $var->label;
            $variable->print = [$var->decimals, $var->width ? min($var->width, 255) : 8, $var->format, 0];
            $variable->write = [$var->decimals, $var->width ? min($var->width, 255) : 8, $var->format, 0];

            if ($var->missing) {
                if ($var->width <= 8) {
                    if (count($var->missing) >= 3) {
                        $variable->missingValuesFormat = 3;
                    } elseif (count($var->missing) == 2) {
                        $variable->missingValuesFormat = -2;
                    } else {
                        $variable->missingValuesFormat = 1;
                    }
                    $variable->missingValues = $var->missing;
                } else {
                    $this->info[Record\Info\LongStringMissingValues::SUBTYPE]->data[$shortName] = $var->missing;
                }
            }

            if ($var->values) {
                if ($var->width > 8) {
                    $this->info[Record\Info\LongStringValueLabels::SUBTYPE]->data[$shortName] = [
                        'width'  => $var->width,
                        'values' => $var->values
                    ];
                } else {
                    $valueLabel = new Record\ValueLabel();
                    foreach ($var->values as $key => $value) {
                        $valueLabel->vars = [$idx + 1];
                        $valueLabel->data[] = [
                            'value' => $var->width > 0 ? Buffer::stringToDouble($key) : $key,
                            'label' => $value
                        ];
                    }
                    $this->valueLabels[] = $valueLabel;
                }
            }

            if (Record\Variable::isVeryLong($var->width)) {
                $this->info[Record\Info\VeryLongString::SUBTYPE]->data[$shortName] = $var->width;
            }
            $this->info[Record\Info\LongVariableNames::SUBTYPE]->data[$shortName] = $var->name;

            $segmentCount = Record\Variable::widthToSegments($var->width);
            for ($i = 0; $i < $segmentCount; $i++) {
                $this->info[Record\Info\VariableDisplayParam::SUBTYPE]->data[] = [
                    $var->measure,
                    $var->columns,
                    $var->align,
                ];
            }

            $dataCount = count($var->data);
            if ($dataCount > $this->header->casesCount) {
                $this->header->casesCount = $dataCount;
            }

            foreach ($var->data as $case => $value) {
                $this->data->matrix[$case][$idx] = $value;
            }

            $this->header->nominalCaseSize += Record\Variable::widthToOcts($var->width);
            $this->variables[] = $variable;
        }

        $this->header->write($this->buffer);

        foreach ($this->variables as $variable) {
            $variable->write($this->buffer);
        }
        foreach ($this->valueLabels as $valueLabel) {
            $valueLabel->write($this->buffer);
        }
        if (!empty($data['documents'])) {
            $this->document->lines = $data['documents'];
            $this->document->write($this->buffer);
        }
        foreach ($this->info as $info) {
            $info->write($this->buffer);
        }
        $this->data->write($this->buffer);
    }

    /**
     * @param $file
     * @return false|int
     */
    public function save($file)
    {
        return $this->buffer->saveToFile($file);
    }
}