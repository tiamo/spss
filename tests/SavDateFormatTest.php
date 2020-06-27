<?php

namespace SPSS\Tests;

use SPSS\Sav\Reader;
use SPSS\Sav\Record;
use SPSS\Sav\Variable;
use SPSS\Sav\Writer;

class SavDateFormatTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProvider()
    {
        $header = array(
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
        );

        $variables = array(
            array(
                'name' => 'DATE_11',
                'format' => Variable::FORMAT_TYPE_DATE, // dd-mmm-yyyy
                'width' => 11,
                'data' => array(
                    '01-Jan-1001',
                    '13-Feb-1989',
                    '10-Jan-2017',
                ),
            ),
            array(
                'name' => 'DATE_9',
                'format' => Variable::FORMAT_TYPE_DATE, // dd-mmm-yy
                'width' => 9,
                'data' => array(
                    '01-Feb-89',
                    '01-Feb-00',
                    '01-Feb-17',
                ),
            ),
            array(
                'name' => 'TIME_5',
                'format' => Variable::FORMAT_TYPE_TIME, // hh:mm
                'width' => 5,
                'data' => array(
                    '00:00',
                    '12:30',
                    '59:59',
                ),
            ),
            array(
                'name' => 'TIME_8',
                'format' => Variable::FORMAT_TYPE_TIME, // hh:mm:ss
                'width' => 8,
                'data' => array(
                    '00:00:00',
                    '12:30:13',
                    '59:59:59',
                ),
            ),
            array(
                'name' => 'TIME_11',
                'format' => Variable::FORMAT_TYPE_TIME, // hh:mm:ss.s
                'width' => 11,
                'decimals' => 2,
                'data' => array(
                    '00:00:00.12',
                    '12:30:13.99',
                    '59:59:59.99',
                ),
            ),
            array(
                'name' => 'DTIME_17',
                'format' => Variable::FORMAT_TYPE_DATETIME, // dd-mmm-yyy hh:mm
                'width' => 17,
                'data' => array(
                    '14-Oct-1989 13:30',
                    '14-Oct-1989 13:30',
                    '14-Oct-1989 13:30',
                ),
            ),
            array(
                'name' => 'DTIME_20',
                'format' => Variable::FORMAT_TYPE_DATETIME, // dd-mmm-yyy hh:mm:ss
                'width' => 20,
                'data' => array(
                    '13-Feb-1989 13:30:59',
                    '13-Feb-1989 13:30:59',
                    '13-Feb-1989 13:30:59',
                ),
            ),
            array(
                'name' => 'DTIME_23',
                'format' => Variable::FORMAT_TYPE_DATETIME, // dd-mmm-yyy hh:mm:ss
                'width' => 23,
                'decimals' => 2,
                'data' => array(
                    '13-Feb-1989 13:30:59.99',
                    '13-Feb-1989 13:30:59.99',
                    '13-Feb-1989 13:30:59.99',
                ),
            ),
            array(
                'name' => 'ADATE_8',
                'format' => Variable::FORMAT_TYPE_ADATE, // mm/dd/yy
                'width' => 8,
                'data' => array(
                    '02/13/89',
                    '02/13/89',
                    '02/13/89',
                ),
            ),
            array(
                'name' => 'ADATE_10',
                'format' => Variable::FORMAT_TYPE_ADATE, // mm/dd/yyyy
                'width' => 10,
                'data' => array(
                    '02/13/1989',
                    '02/13/1989',
                    '02/13/1989',
                ),
            ),
            array(
                'name' => 'JDATE_5',
                'format' => Variable::FORMAT_TYPE_JDATE, // Julian date - yyddd
                'width' => 5,
                'data' => array(
                    '90301',
                    '90301',
                    '90301',
                ),
            ),
            array(
                'name' => 'JDATE_7',
                'format' => Variable::FORMAT_TYPE_JDATE, // Julian date - yyyyddd
                'width' => 7,
                'data' => array(
                    '1990301',
                    '1990301',
                    '1990301',
                ),
            ),
            array(
                'name' => 'DTIME_9',
                'format' => Variable::FORMAT_TYPE_DTIME, // dd hh:mm
                'width' => 9,
                'data' => array(
                    '13 13:13',
                    '14 14:14',
                    '15 15:15',
                ),
            ),
            array(
                'name' => 'DTIME_12',
                'format' => Variable::FORMAT_TYPE_DTIME, // dd hh:mm:ss
                'width' => 12,
                'data' => array(
                    '13 13:13:13',
                    '14 14:14:14',
                    '15 15:15:15',
                ),
            ),
            array(
                'name' => 'DTIME_15',
                'format' => Variable::FORMAT_TYPE_DTIME, // dd hh:mm:ss.s
                'width' => 15,
                'decimals' => 2,
                'data' => array(
                    '13 13:13:13.13',
                    '14 14:14:14.14',
                    '15 15:15:15.15',
                ),
            ),
            array(
                'name' => 'WKDAY',
                'format' => Variable::FORMAT_TYPE_WKDAY,
                'width' => 3,
                'data' => array(
                    'Sun',
                    'Mon',
                    'Tue',
                ),
            ),
            array(
                'name' => 'WKDAY_9',
                'format' => Variable::FORMAT_TYPE_WKDAY, // Monday
                'width' => 9,
                'data' => array(
                    'Sunday',
                    'Monday',
                    'Tuesday',
                ),
            ),
            array(
                'name' => 'MONTH',
                'format' => Variable::FORMAT_TYPE_MONTH, // Jan
                'width' => 3,
                'data' => array(
                    'Jan',
                    'Feb',
                    'Mar',
                ),
            ),
            array(
                'name' => 'MONTH_9',
                'format' => Variable::FORMAT_TYPE_MONTH, // January
                'width' => 9,
                'data' => array(
                    'January',
                    'February',
                    'March',
                ),
            ),
            array(
                'name' => 'MOYR_6',
                'format' => Variable::FORMAT_TYPE_MOYR, // mmm yy
                'width' => 6,
                'data' => array(
                    'OCT 90',
                    'OCT 90',
                    'OCT 90',
                ),
            ),
            array(
                'name' => 'MOYR_8',
                'format' => Variable::FORMAT_TYPE_MOYR, // mmm yyyy
                'width' => 8,
                'data' => array(
                    'OCT 1990',
                    'OCT 1990',
                    'OCT 1990',
                ),
            ),
            array(
                'name' => 'QYR_6',
                'format' => Variable::FORMAT_TYPE_QYR, // q Q yy
                'width' => 6,
                'data' => array(
                    '4 Q 90',
                    '4 Q 90',
                    '4 Q 90',
                ),
            ),
            array(
                'name' => 'QYR_8',
                'format' => Variable::FORMAT_TYPE_QYR, // q Q yyyy
                'width' => 8,
                'data' => array(
                    '4 Q 1990',
                    '4 Q 1990',
                    '4 Q 1990',
                ),
            ),
            array(
                'name' => 'WKYR_8',
                'format' => Variable::FORMAT_TYPE_WKYR, // ww WK yy
                'width' => 8,
                'data' => array(
                    '43 WK 90',
                    '43 WK 90',
                    '43 WK 90',
                ),
            ),
            array(
                'name' => 'WKYR_10',
                'format' => Variable::FORMAT_TYPE_WKYR, // ww WK yyyy
                'width' => 10,
                'data' => array(
                    '43 WK 1990',
                    '43 WK 1990',
                    '43 WK 1990',
                ),
            ),
            array(
                'name' => 'EDATE_8',
                'format' => Variable::FORMAT_TYPE_EDATE, // dd.mm.yy
                'width' => 8,
                'data' => array(
                    '28.10.90',
                    '28.10.90',
                    '28.10.90',
                ),
            ),
            array(
                'name' => 'EDATE_10',
                'format' => Variable::FORMAT_TYPE_EDATE, // dd.mm.yyyy
                'width' => 10,
                'data' => array(
                    '28.10.1990',
                    '28.10.1990',
                    '28.10.1990',
                ),
            ),
            array(
                'name' => 'SDATE_8',
                'format' => Variable::FORMAT_TYPE_SDATE, // yy/mm/dd
                'width' => 8,
                'data' => array(
                    '90/10/28',
                    '90/10/28',
                    '90/10/28',
                ),
            ),
            array(
                'name' => 'SDATE_10',
                'format' => Variable::FORMAT_TYPE_SDATE, // yyyy/mm/dd
                'width' => 10,
                'data' => array(
                    '1990/10/28',
                    '1990/10/28',
                    '1990/10/28',
                ),
            ),
        );

        return array(
            array(
                compact('header', 'variables'),
            ),
        );
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array $data
     *
     * @throws \Throwable
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

            ++$index;
        }
    }
}
