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
    public $data = array();

    /**
     * @throws \SPSS\Exception
     */
    public function read(Buffer $buffer)
    {
        parent::read($buffer);
        $buffer = $buffer->allocate($this->dataCount * $this->dataSize);
        while ($varNameLength = $buffer->readInt()) {
            $varName = $buffer->readString($varNameLength);
            $varWidth = $buffer->readInt(); // The width of the variable, in bytes, which will be between 9 and 32767
            $valuesCount = $buffer->readInt();
            $this->data[$varName] = array(
                'width' => $varWidth,
                'values' => array(),
            );
            for ($i = 0; $i < $valuesCount; ++$i) {
                $valueLength = $buffer->readInt();
                $value = rtrim($buffer->readString($valueLength));
                $labelLength = $buffer->readInt();
                $label = rtrim($buffer->readString($labelLength));
                $this->data[$varName]['values'][$value] = $label;
            }
        }
    }

    public function write(Buffer $buffer)
    {
        $localBuffer = Buffer::factory('', array('memory' => true));
        foreach ($this->data as $varName => $data) {
            if (!isset($data['width'])) {
                throw new \InvalidArgumentException('width required');
            }
            if (!isset($data['values'])) {
                throw new \InvalidArgumentException('values required');
            }
            $width = (int) $data['width'];
            $localBuffer->writeInt(mb_strlen($varName));
            $localBuffer->writeString($varName);
            $localBuffer->writeInt($width);
            $localBuffer->writeInt(\count($data['values']));
            foreach ($data['values'] as $value => $label) {
                $localBuffer->writeInt($width);
                $localBuffer->writeString($value, $width);
                $localBuffer->writeInt(mb_strlen($label));
                $localBuffer->writeString($label);
            }
        }

        // retrieve bytes count
        $this->dataCount = $localBuffer->position();
        if ($this->dataCount > 0) {
            parent::write($buffer);
            $localBuffer->rewind();
            $buffer->writeStream($localBuffer->getStream());
        }
    }
}
