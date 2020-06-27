<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use SPSS\Sav\Variable;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function checkHeader(array $header, Reader $reader)
    {
        foreach ($header as $key => $value) {
            $this->assertEquals(
                $value,
                $reader->header->{$key},
                sprintf(
                    'Header line `%s` is invalid: expected `%s` but got `%s`.',
                    $key,
                    $value, $reader->header->{$key}
                )
            );
        }
    }

    /**
     * @param array $opts
     *
     * @return array
     */
    protected function generateVariable($opts = array())
    {
        $opts = array_merge(array(
            'id' => uniqid('', true),
            'numeric' => mt_rand(0, 1),
            'casesCount' => 0,
        ), $opts
        );

        $var = array(
            'name' => sprintf('VAR%s', $opts['id']),
            'label' => sprintf('Label (%s)', $opts['id']),
            'columns' => mt_rand(0, 100),
            'alignment' => mt_rand(0, 2),
            'measure' => mt_rand(1, 3),
            'width' => 8,
        );

        if ($opts['numeric']) {
            $var['format'] = Variable::FORMAT_TYPE_F;
            $var['decimals'] = mt_rand(0, 2);
            for ($c = 0; $c < $opts['casesCount']; ++$c) {
                $var['data'][$c] = mt_rand(1, 99999) . '.' . mt_rand(1, 99999);
            }
        } else {
            $var['format'] = Variable::FORMAT_TYPE_A;

            // TODO: test > 255
            $var['width'] = mt_rand(2, 2000);
            $var['decimals'] = 0;
            for ($c = 0; $c < $opts['casesCount']; ++$c) {
                $var['data'][$c] = trim($this->generateRandomString(mt_rand(0, $var['width'])));
            }
        }

        return $var;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    protected function generateRandomString($length = 10)
    {
        $characters = '_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = \strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; ++$i) {
            $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }

        return trim($randomString);
    }
}
