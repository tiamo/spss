<?php

namespace SPSS\Sav\Record;

use SPSS\Buffer;
use SPSS\Exception;
use SPSS\Sav\Record;
use SPSS\Utils;

/**
 * The value label records documented in this section are used for numeric and short string variables only.
 * Long string variables may have value labels, but their value labels are recorded using a different record type
 *
 * @see Info\LongStringValueLabels
 */
class ValueLabel extends Record
{
    const TYPE = 3;
    const LABEL_MAX_LENGTH = 255;

    /**
     * @var array
     */
    public $labels = [];

    /**
     * @var array
     * A list of dictionary indexes of variables to which to apply the value labels
     * String variables wider than 8 bytes may not be specified in this list.
     */
    public $indexes = [];

    /**
     * @var Variable[]
     */
    protected $variables = [];

    /**
     * @param  array  $variables
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
    }

    /**
     * @param  Buffer  $buffer
     * @throws Exception
     */
    public function read(Buffer $buffer)
    {
        /** @var int $labelCount Number of value labels present in this record. */
        $labelCount = $buffer->readInt();

        for ($i = 0; $i < $labelCount; $i++) {
            // A numeric value or a short string value padded as necessary to 8 bytes in length.
            $value = $buffer->readDouble();
            $labelLength = ord($buffer->read(1));
            $label = $buffer->readString(Utils::roundUp($labelLength + 1, 8) - 1);
            $this->labels[] = [
                'value' => $value,
                'label' => rtrim($label),
            ];
        }

        // The value label variables record is always immediately followed after a value label record.
        $recType = $buffer->readInt();
        if ($recType !== 4) {
            throw new Exception(
                sprintf('Error reading Variable Index record: bad record type [%s]. Expecting Record Type 4.', $recType)
            );
        }

        // Number of variables that the associated value labels from the value label record are to be applied.
        $varCount = $buffer->readInt();
        for ($i = 0; $i < $varCount; $i++) {

            $varIndex = $buffer->readInt() - 1;

            // Decode values for short variables
            if (isset($this->variables[$varIndex])) {
                $varWidth = $this->variables[$varIndex]->width;
                if ($varWidth > 0) {
                    foreach ($this->labels as $labelIdx => $label) {
                        $this->labels[$labelIdx]['value'] = rtrim(Utils::doubleToString($label['value']));
                    }
                }
            }

            $this->indexes[] = $varIndex;
        }

    }

    /**
     * @param  Buffer  $buffer
     */
    public function write(Buffer $buffer)
    {
        $convertToDouble = false;
        $varIndex = reset($this->indexes);
        if ($varIndex !== false && isset($this->variables[$varIndex - 1])) {
            $varWidth = $this->variables[$varIndex - 1]->width;
            $convertToDouble = $varWidth > 0;
        }

        // Value label record.
        $buffer->writeInt(self::TYPE);
        $buffer->writeInt(count($this->labels));
        foreach ($this->labels as $item) {
            $labelLength = mb_strlen($item['label']);
            $labelLength = min($labelLength, self::LABEL_MAX_LENGTH);

            if ($convertToDouble) {
                $item['value'] = Utils::stringToDouble($item['value']);
            }

            $buffer->writeDouble($item['value']);
            $buffer->write(chr($labelLength));
            $buffer->writeString($item['label'], Utils::roundUp($labelLength + 1, 8) - 1);
        }

        // Value label variable record.
        $buffer->writeInt(4);
        $buffer->writeInt(count($this->indexes));
        foreach ($this->indexes as $varIndex) {
            $buffer->writeInt($varIndex);
        }
    }
}
