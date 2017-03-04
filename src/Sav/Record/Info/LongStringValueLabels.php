<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Sav\Record\Info;

class LongStringValueLabels extends Info
{
    const SUBTYPE = 21;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @param Buffer $buffer
     */
    public function read(Buffer $buffer)
    {
        parent::read($buffer);
        $buffer = $buffer->allocate($this->dataCount * $this->dataSize);
        while ($varNameLength = $buffer->readInt()) {
            $varName = trim($buffer->readString($varNameLength));
            $varWidth = $buffer->readInt(); // The width of the variable, in bytes, which will be between 9 and 32767
            $valuesCount = $buffer->readInt();
            $this->data[$varName] = [
                'width'  => $varWidth,
                'values' => [],
            ];
            for ($i = 0; $i < $valuesCount; $i++) {
                $valueLength = $buffer->readInt();
                $value = trim($buffer->readString($valueLength));
                $lebelLength = $buffer->readInt();
                $label = trim($buffer->readString($lebelLength));
                $this->data[$varName]['values'][$value] = $label;
            }
        }
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
        $localBuffer = Buffer::factory();
        foreach ($this->data as $varName => $data) {
            if (!isset($data['width'])) {
                throw new \InvalidArgumentException('width required');
            }
            if (!isset($data['values'])) {
                throw new \InvalidArgumentException('values required');
            }
            $width = (int)$data['width'];
            $localBuffer->writeInt(strlen($varName));
            $localBuffer->writeString($varName);
            $localBuffer->writeInt($width);
            $localBuffer->writeInt(count($data['values']));
            foreach ($data['values'] as $value => $label) {
                $localBuffer->writeInt($width);
                $localBuffer->writeString($value, $width);
                $localBuffer->writeInt(strlen($label));
                $localBuffer->writeString($label);
            }
        }
        $this->dataCount = $localBuffer->position();
        if ($this->dataCount > 0) {
            parent::write($buffer);
            $localBuffer->rewind();
            $buffer->writeStream($localBuffer->getStream());
        }
    }
}