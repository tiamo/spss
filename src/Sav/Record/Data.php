<?php

namespace SPSS\Sav\Record;

use SPSS\Buffer;
use SPSS\Exception;
use SPSS\Sav\Record;

class Data extends Record
{
    const TYPE = 999;
    const OPCODE_CONTINUE = 0;
    const OPCODE_END_DATA = 252;
    const OPCODE_RAW_DATA = 253;
    const OPCODE_WHITESPACES = 254;
    const OPCODE_SYSMIS = 255;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var array
     */
    private $opcodes = [];

    /**
     * @var int
     */
    private $opcodeIndex = 8;

    /**
     * @param Buffer $buffer
     * @throws Exception
     */
    public function read(Buffer $buffer)
    {
        if ($buffer->readInt() != 0) {
            throw new Exception('Error reading data record. Non-zero value found.');
        }
        if (!isset($buffer->context->variables)) {
            throw new Exception('Variables required in buffer context.');
        }
        if (!isset($buffer->context->header)) {
            throw new Exception('Header required in buffer context.');
        }
        if (!isset($buffer->context->info)) {
            throw new Exception('Info required in buffer context.');
        }

        $compressed = $buffer->context->header->compression;
        $casesCount = $buffer->context->header->casesCount;
        $variables = $buffer->context->variables;

        if (isset($buffer->context->info[Record\Info\MachineFloatingPoint::SUBTYPE])) {
            $sysmis = $buffer->context->info[Record\Info\MachineFloatingPoint::SUBTYPE]->sysmis;
        } else {
            $sysmis = NAN;
        }

        $veryLongStrings = [];
        if (isset($buffer->context->info[Record\Info\VeryLongString::SUBTYPE])) {
            $veryLongStrings = $buffer->context->info[Record\Info\VeryLongString::SUBTYPE]->data;
        }

        for ($case = 0; $case < $casesCount; $case++) {
            $parent = $octs = 0;
            foreach ($variables as $index => $var) {
                if ($var->type == 0) {
                    if (!$compressed) {
                        $this->data[$case][$index] = $buffer->readDouble();
                    } else {
                        $opcode = $this->readOpcode($buffer);
                        switch ($opcode) {
                            case self::OPCODE_CONTINUE;
                                break;
                            case self::OPCODE_END_DATA;
                                throw new Exception('Error reading data: unexpected end of compressed data file (cluster code 252)');
                                break;
                            case self::OPCODE_RAW_DATA;
                                $this->data[$case][$index] = $buffer->readDouble();
                                break;
                            case self::OPCODE_WHITESPACES;
                                $this->data[$case][$index] = 0.0;
                                break;
                            case self::OPCODE_SYSMIS;
                                $this->data[$case][$index] = $sysmis;
                                break;
                            default:
                                $this->data[$case][$index] = $opcode - $buffer->context->header->bias;
                                break;
                        }
                    }
                } else {
                    $value = '';
                    if (!$compressed) {
                        $value = $buffer->readString(8);
                    } else {
                        $opcode = $this->readOpcode($buffer);
                        switch ($opcode) {
                            case self::OPCODE_CONTINUE;
                                break;
                            case self::OPCODE_END_DATA;
                                throw new Exception('Error reading data: unexpected end of compressed data file (cluster code 252)');
                                break;
                            case self::OPCODE_RAW_DATA;
                                $value = $buffer->readString(8);
                                break;
                            case self::OPCODE_WHITESPACES;
                                $value = '        ';
                                break;
//                            case self::OPCODE_SYSMIS:
//                                $value = '';
//                                break;
                        }
                    }
                    if ($parent) {
                        $this->data[$case][$parent] .= $value;
                        $octs--;
                        if ($octs <= 0) {
                            $this->data[$case][$parent] = trim($this->data[$case][$parent]);
                            $parent = 0;
                        }
                    } else {
                        $width = isset($veryLongStrings[$var->name]) ? $veryLongStrings[$var->name] : $var->type;
                        if ($width > 0) {
                            $octs = Variable::widthToOcts($width) - 1;
                            if ($octs > 0) {
                                $parent = $index;
                            } else {
                                $value = rtrim($value);
                            }
                            $this->data[$case][$index] = $value;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Buffer $buffer
     */
    public function write(Buffer $buffer)
    {
    }

    /**
     * @param Buffer $buffer
     * @return int
     */
    public function readOpcode(Buffer $buffer)
    {
        if ($this->opcodeIndex >= 8) {
            $this->opcodes = $buffer->readBytes(8);
            $this->opcodeIndex = 0;
        }
        return 0xFF & $this->opcodes[$this->opcodeIndex++];
    }
}
