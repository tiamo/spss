<?php

namespace SPSS\Sav\Record\Info;

use SPSS\Buffer;
use SPSS\Sav\Record\Info;

class LongStringMissingValues extends Info
{
    const SUBTYPE = 22;

    /**
     * @throws \SPSS\Exception
     */
    public function read(Buffer $buffer)
    {
        parent::read($buffer);
        $buffer = $buffer->allocate($this->dataCount * $this->dataSize);
        while ($varNameLength = $buffer->readInt()) {
            $varName = trim($buffer->readString($varNameLength));
            $count = \ord($buffer->read(1));
            $this->data[$varName] = array();
            $valueLength = $buffer->readInt();
            for ($i = 0; $i < $count; ++$i) {
                $value = $buffer->readString($valueLength);
                $this->data[$varName][] = rtrim($value);
            }
        }
    }

    public function write(Buffer $buffer)
    {
        if ($this->data) {
            $localBuffer = Buffer::factory();
            foreach ($this->data as $varName => $values) {
                $localBuffer->writeInt(mb_strlen($varName));
                $localBuffer->writeString($varName);
                $localBuffer->write(\chr(\count($values)), 1);
                $localBuffer->writeInt(8);
                foreach ($values as $value) {
                    $localBuffer->writeString($value, 8);
                }
            }
            $this->dataCount = $localBuffer->position();
            parent::write($buffer);
            $localBuffer->rewind();
            $buffer->writeStream($localBuffer->getStream(), $this->dataCount);
        }
    }
}
