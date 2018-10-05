<?php

namespace SPSS\Sav;

use SPSS\Buffer;
use SPSS\Exception;
use SPSS\Utils;

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
     *
     * @param array $data
     * @throws \Exception
     */
    public function __construct($data = [])
    {
        $this->buffer = Buffer::factory();
        $this->buffer->context = $this;

        if (! empty($data)) {
            $this->write($data);
        }
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    public function write($data)
    {
        $this->header = new Record\Header($data['header']);
        $this->header->nominalCaseSize = 0;
        $this->header->casesCount = 0;

        $this->info[Record\Info\MachineInteger::SUBTYPE] = $this->prepareInfoRecord(
            Record\Info\MachineInteger::class,
            $data
        );

        $this->info[Record\Info\MachineFloatingPoint::SUBTYPE] = $this->prepareInfoRecord(
            Record\Info\MachineFloatingPoint::class,
            $data
        );

        $this->info[Record\Info\VariableDisplayParam::SUBTYPE] = new Record\Info\VariableDisplayParam();
        $this->info[Record\Info\LongVariableNames::SUBTYPE] = new Record\Info\LongVariableNames();
        $this->info[Record\Info\VeryLongString::SUBTYPE] = new Record\Info\VeryLongString();
        $this->info[Record\Info\ExtendedNumberOfCases::SUBTYPE] = $this->prepareInfoRecord(
            Record\Info\ExtendedNumberOfCases::class,
            $data
        );
        $this->info[Record\Info\VariableAttributes::SUBTYPE] = new Record\Info\VariableAttributes();
        $this->info[Record\Info\LongStringValueLabels::SUBTYPE] = new Record\Info\LongStringValueLabels();
        $this->info[Record\Info\LongStringMissingValues::SUBTYPE] = new Record\Info\LongStringMissingValues();

        $this->data = new Record\Data();

        /** @var Variable $var */
        foreach ($data['variables'] as $idx => $var) {

            if (is_array($var)) {
                $var = new Variable($var);
            }

            if (! preg_match('/^[A-Za-z0-9_]+$/', $var->name)) {
                throw new \Exception(
                    sprintf('Variable name `%s` contains an illegal character.', $var->name)
                );
            }

            $variable = new Record\Variable();
            $variable->name = 'V' . str_pad($idx + 1, 7, 0, STR_PAD_LEFT);
            // $variable->name = $var->name;

            // TODO: test
            if ($var->format == Variable::FORMAT_TYPE_A) {
                $variable->width = $var->width;
            } else {
                $variable->width = 0;
            }

            $variable->label = $var->label;
            $variable->print = [
                0,
                $var->format,
                $var->width ? min($var->width, 255) : 8,
                $var->decimals,
            ];
            $variable->write = [
                0,
                $var->format,
                $var->width ? min($var->width, 255) : 8,
                $var->decimals,
            ];

            // TODO: refactory
            $shortName = $variable->name;
            $longName = $var->name;

            if ($var->attributes) {
                $this->info[Record\Info\VariableAttributes::SUBTYPE][$longName] = $var->attributes;
            }

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
                    $this->info[Record\Info\LongStringMissingValues::SUBTYPE][$shortName] = $var->missing;
                }
            }

            $this->variables[] = $variable;

            if ($var->values) {
                if ($variable->width > 8) {
                    $this->info[Record\Info\LongStringValueLabels::SUBTYPE][$longName] = [
                        'width' => $var->width,
                        'values' => $var->values,
                    ];
                } else {
                    $valueLabel = new Record\ValueLabel([
                        'variables' => $this->variables,
                    ]);
                    foreach ($var->values as $key => $value) {
                        $valueLabel->labels[] = [
                            'value' => $key,
                            'label' => $value,
                        ];
                        $valueLabel->indexes = [$idx + 1];
                    }
                    $this->valueLabels[] = $valueLabel;
                }
            }

            $this->info[Record\Info\LongVariableNames::SUBTYPE][$shortName] = $var->name;

            if (Record\Variable::isVeryLong($var->width)) {
                $this->info[Record\Info\VeryLongString::SUBTYPE][$shortName] = $var->width;
            }

            $segmentCount = Utils::widthToSegments($var->width);

            for ($i = 0; $i < $segmentCount; $i++) {
                $this->info[Record\Info\VariableDisplayParam::SUBTYPE][$idx] = [
                    $var->getMeasure(),
                    $var->getColumns(),
                    $var->getAlignment(),
                ];
            }

            $dataCount = count($var->data);
            if ($dataCount > $this->header->casesCount) {
                $this->header->casesCount = $dataCount;
            }

            foreach ($var->data as $case => $value) {
                $this->data->matrix[$case][$idx] = $value;
            }

            $this->header->nominalCaseSize += Utils::widthToOcts($var->width);
        }

        // write header
        $this->header->write($this->buffer);

        // write variables
        foreach ($this->variables as $variable) {
            $variable->write($this->buffer);
        }

        // write valueLabels
        foreach ($this->valueLabels as $valueLabel) {
            $valueLabel->write($this->buffer);
        }

        // write documents
        if (! empty($data['documents'])) {
            $this->document = new Record\Document([
                    'lines' => $data['documents'],
                ]
            );
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

    /**
     * @return \SPSS\Buffer
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * @param string $className
     * @param array $data
     * @param string $group
     * @return array
     * @throws Exception
     */
    private function prepareInfoRecord($className, $data, $group = 'info')
    {
        if (! class_exists($className)) {
            throw new Exception('Unknown class');
        }
        $key = lcfirst(substr($className, strrpos($className, '\\') + 1));

        return new $className(
            isset($data[$group]) && isset($data[$group][$key]) ?
                $data[$group][$key] :
                []
        );
    }
}
