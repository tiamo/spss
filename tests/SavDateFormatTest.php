<?php

namespace SPSS\Tests;

use SPSS\Sav\Record;
use SPSS\Sav\Variable;
use SPSS\Sav\Reader;
use SPSS\Sav\Writer;

class SavDateFormatTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProvider()
    {
        $header = [
            'recType' => Record\Header::NORMAL_REC_TYPE,
            'prodName' => '@(#) SPSS DATA FILE',
            'layoutCode' => 2,
            'nominalCaseSize' => 47,
            'casesCount' => 3,
            'compression' => 1,
            'weightIndex' => 0,
            'bias' => 100,
            'creationDate' => date('d M y'),
            'creationTime' => date('H:i:s'),
            'fileLabel' => 'test dates',
        ];

        $variables = [
            [
                'name' => 'DATE_11',
                'format' => Variable::FORMAT_TYPE_DATE, // dd-mmm-yyyy
                'width' => 11,
                'data' => [
                    '01-Jan-1001',
                    '13-Feb-1989',
                    '10-Jan-2017',
                ],
            ],
            [
                'name' => 'DATE_9',
                'format' => Variable::FORMAT_TYPE_DATE, // dd-mmm-yy
                'width' => 9,
                'data' => [
                    '01-Feb-89',
                    '01-Feb-00',
                    '01-Feb-17',
                ],
            ],
            [
                'name' => 'TIME_5',
                'format' => Variable::FORMAT_TYPE_TIME, // hh:mm
                'width' => 5,
                'data' => [
                    '00:00',
                    '12:30',
                    '59:59',
                ],
            ],
            [
                'name' => 'TIME_8',
                'format' => Variable::FORMAT_TYPE_TIME, // hh:mm:ss
                'width' => 8,
                'data' => [
                    '00:00:00',
                    '12:30:13',
                    '59:59:59',
                ],
            ],
            [
                'name' => 'TIME_11',
                'format' => Variable::FORMAT_TYPE_TIME, // hh:mm:ss.s
                'width' => 11,
                'decimals' => 2,
                'data' => [
                    '00:00:00.12',
                    '12:30:13.99',
                    '59:59:59.99',
                ],
            ],
            [
                'name' => 'DTIME_17',
                'format' => Variable::FORMAT_TYPE_DATETIME, // dd-mmm-yyy hh:mm
                'width' => 17,
                'data' => [
                    '14-Oct-1989 13:30',
                    '14-Oct-1989 13:30',
                    '14-Oct-1989 13:30',
                ],
            ],
            [
                'name' => 'DTIME_20',
                'format' => Variable::FORMAT_TYPE_DATETIME, // dd-mmm-yyy hh:mm:ss
                'width' => 20,
                'data' => [
                    '13-Feb-1989 13:30:59',
                    '13-Feb-1989 13:30:59',
                    '13-Feb-1989 13:30:59',
                ],
            ],
            [
                'name' => 'DTIME_23',
                'format' => Variable::FORMAT_TYPE_DATETIME, // dd-mmm-yyy hh:mm:ss
                'width' => 23,
                'decimals' => 2,
                'data' => [
                    '13-Feb-1989 13:30:59.99',
                    '13-Feb-1989 13:30:59.99',
                    '13-Feb-1989 13:30:59.99',
                ],
            ],
            [
                'name' => 'ADATE_8',
                'format' => Variable::FORMAT_TYPE_ADATE, // mm/dd/yy
                'width' => 8,
                'data' => [
                    '02/13/89',
                    '02/13/89',
                    '02/13/89',
                ],
            ],
            [
                'name' => 'ADATE_10',
                'format' => Variable::FORMAT_TYPE_ADATE, // mm/dd/yyyy
                'width' => 10,
                'data' => [
                    '02/13/1989',
                    '02/13/1989',
                    '02/13/1989',
                ],
            ],
            [
                'name' => 'JDATE_5',
                'format' => Variable::FORMAT_TYPE_JDATE, // Julian date - yyddd
                'width' => 5,
                'data' => [
                    '90301',
                    '90301',
                    '90301',
                ],
            ],
            [
                'name' => 'JDATE_7',
                'format' => Variable::FORMAT_TYPE_JDATE, // Julian date - yyyyddd
                'width' => 7,
                'data' => [
                    '1990301',
                    '1990301',
                    '1990301',
                ],
            ],
            [
                'name' => 'DTIME_9',
                'format' => Variable::FORMAT_TYPE_DTIME, // dd hh:mm
                'width' => 9,
                'data' => [
                    '13 13:13',
                    '14 14:14',
                    '15 15:15',
                ],
            ],
            [
                'name' => 'DTIME_12',
                'format' => Variable::FORMAT_TYPE_DTIME, // dd hh:mm:ss
                'width' => 12,
                'data' => [
                    '13 13:13:13',
                    '14 14:14:14',
                    '15 15:15:15',
                ],
            ],
            [
                'name' => 'DTIME_15',
                'format' => Variable::FORMAT_TYPE_DTIME, // dd hh:mm:ss.s
                'width' => 15,
                'decimals' => 2,
                'data' => [
                    '13 13:13:13.13',
                    '14 14:14:14.14',
                    '15 15:15:15.15',
                ],
            ],
            [
                'name' => 'WKDAY',
                'format' => Variable::FORMAT_TYPE_WKDAY,
                'width' => 3,
                'data' => [
                    'Sun',
                    'Mon',
                    'Tue',
                ],
            ],
            [
                'name' => 'WKDAY_9',
                'format' => Variable::FORMAT_TYPE_WKDAY, // Monday
                'width' => 9,
                'data' => [
                    'Sunday',
                    'Monday',
                    'Tuesday',
                ],
            ],
            [
                'name' => 'MONTH',
                'format' => Variable::FORMAT_TYPE_MONTH, // Jan
                'width' => 3,
                'data' => [
                    'Jan',
                    'Feb',
                    'Mar',
                ],
            ],
            [
                'name' => 'MONTH_9',
                'format' => Variable::FORMAT_TYPE_MONTH, // January
                'width' => 9,
                'data' => [
                    'January',
                    'February',
                    'March',
                ],
            ],
            [
                'name' => 'MOYR_6',
                'format' => Variable::FORMAT_TYPE_MOYR, // mmm yy
                'width' => 6,
                'data' => [
                    'OCT 90',
                    'OCT 90',
                    'OCT 90',
                ],
            ],
            [
                'name' => 'MOYR_8',
                'format' => Variable::FORMAT_TYPE_MOYR, // mmm yyyy
                'width' => 8,
                'data' => [
                    'OCT 1990',
                    'OCT 1990',
                    'OCT 1990',
                ],
            ],
            [
                'name' => 'QYR_6',
                'format' => Variable::FORMAT_TYPE_QYR, // q Q yy
                'width' => 6,
                'data' => [
                    '4 Q 90',
                    '4 Q 90',
                    '4 Q 90',
                ],
            ],
            [
                'name' => 'QYR_8',
                'format' => Variable::FORMAT_TYPE_QYR, // q Q yyyy
                'width' => 8,
                'data' => [
                    '4 Q 1990',
                    '4 Q 1990',
                    '4 Q 1990',
                ],
            ],
            [
                'name' => 'WKYR_8',
                'format' => Variable::FORMAT_TYPE_WKYR, // ww WK yy
                'width' => 8,
                'data' => [
                    '43 WK 90',
                    '43 WK 90',
                    '43 WK 90',
                ],
            ],
            [
                'name' => 'WKYR_10',
                'format' => Variable::FORMAT_TYPE_WKYR, // ww WK yyyy
                'width' => 10,
                'data' => [
                    '43 WK 1990',
                    '43 WK 1990',
                    '43 WK 1990',
                ],
            ],
            [
                'name' => 'EDATE_8',
                'format' => Variable::FORMAT_TYPE_EDATE, // dd.mm.yy
                'width' => 8,
                'data' => [
                    '28.10.90',
                    '28.10.90',
                    '28.10.90',
                ],
            ],
            [
                'name' => 'EDATE_10',
                'format' => Variable::FORMAT_TYPE_EDATE, // dd.mm.yyyy
                'width' => 10,
                'data' => [
                    '28.10.1990',
                    '28.10.1990',
                    '28.10.1990',
                ],
            ],
            [
                'name' => 'SDATE_8',
                'format' => Variable::FORMAT_TYPE_SDATE, // yy/mm/dd
                'width' => 8,
                'data' => [
                    '90/10/28',
                    '90/10/28',
                    '90/10/28',
                ],
            ],
            [
                'name' => 'SDATE_10',
                'format' => Variable::FORMAT_TYPE_SDATE, // yyyy/mm/dd
                'width' => 10,
                'data' => [
                    '1990/10/28',
                    '1990/10/28',
                    '1990/10/28',
                ],
            ],
        ];

        return [
            [
                compact('header', 'variables'),
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param array $data
     * @throws \Exception
     */
    public function testWriteRead($data)
    {
        $writer = new Writer($data);

        $buffer = $writer->getBuffer();
        $buffer->rewind();

        $stream = $buffer->getStream();

        $reader = Reader::fromString($stream)->read();

        $this->checkHeader($data['header'], $reader);

        $index = 0;
        foreach ($data['variables'] as $var) {
            /** @var Record\Variable $_var */
            $_var = $reader->variables[$index];

            // TODO: test long variables
            // $this->assertEquals($var['name'], $_var->name);

            $this->assertEquals($var['format'], $_var->print[1]);
            $this->assertEquals($var['width'], $_var->print[2]);

            $index++;
        }
    }

}
