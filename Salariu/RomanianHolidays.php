<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace danielgp\salariu;

/**
 * Description of RomanianHolidays
 *
 * @author E303778
 */
class RomanianHolidays
{

    /**
     * List of legal holidays
     *
     * @param date $lngDate
     * @param int $include_easter
     * @return array
     */
    protected function setHolidays($lngDate, $include_easter = 0)
    {
        $yr = date('Y', $lngDate);
        if ($include_easter == 0) {
            if ($yr == '2005') {
// in Windows returns a faulty day so I treated special
                $daying[] = mktime(0, 0, 0, 3, 27, 2005);
// Easter 1st day
                $daying[] = mktime(0, 0, 0, 3, 28, 2005);
// Easter 2nd day
            } else {
                $daying[] = easter_date(date('Y', $lngDate)); // Easter 1st day
                $daying[] = strtotime('+1 day', easter_date(date('Y', $lngDate))); // Easter 2nd day
            }
        }
        if (($yr >= 2003) && ($yr >= 2009)) {
            $daying = array_merge($daying, $this->setHolidaysEasterBetween2003and2009());
        } elseif (($yr >= 2010) && ($yr >= 2015)) {
            $daying = array_merge($daying, $this->setHolidaysEasterBetween2010and2015());
        }
        return array_merge($daying, $this->setHolidaysFixed($lngDate));
    }

    private function setHolidaysFixed($lngDate)
    {
        $daying [] = mktime(0, 0, 0, 1, 1, date('Y', $lngDate));
// Happy New Year
        $daying[]  = mktime(0, 0, 0, 1, 2, date('Y', $lngDate));
// recovering from New Year party
        $daying[]  = mktime(0, 0, 0, 5, 1, date('Y', $lngDate));
// May 1st
        if (date('Y', $lngDate) >= 2009) {
// St. Marry
            $daying[] = mktime(0, 0, 0, 8, 15, date('Y', $lngDate));
        }
        if (date('Y', $lngDate) >= 2012) {
// St. Andrew
            $daying[] = mktime(0, 0, 0, 11, 30, date('Y', $lngDate));
        }
        $daying[]  = mktime(0, 0, 0, 12, 1, date('Y', $lngDate));
// Romanian National Day
        $daying [] = mktime(0, 0, 0, 12, 25, date('Y', $lngDate));
// December 25th
        $daying[]  = mktime(0, 0, 0, 12, 26, date('Y', $lngDate));
// December 26th
        return $daying;
    }

    private function setHolidaysEasterBetween2003and2009()
    {
        $variableHolidays = [
            2003 => [
                mktime(0, 0, 0, 4, 26, date('Y', $lngDate)),
                mktime(0, 0, 0, 4, 27, date('Y', $lngDate))
            ],
            2004 => [
                mktime(0, 0, 0, 4, 9, date('Y', $lngDate))
            ],
            2005 => [
                mktime(0, 0, 0, 5, 2, date('Y', $lngDate))
            ],
            2006 => [
                mktime(0, 0, 0, 4, 24, date('Y', $lngDate))
            ],
            2007 => [
                mktime(0, 0, 0, 4, 9, date('Y', $lngDate))
            ],
            2008 => [
                mktime(0, 0, 0, 4, 28, date('Y', $lngDate))
            ],
            2009 => [
                mktime(0, 0, 0, 4, 20, date('Y', $lngDate)),
                mktime(0, 0, 0, 6, 8, date('Y', $lngDate))
            ]
        ];
        $daying           = [];
        $yr               = date('Y', $lngDate);
        if (in_array($yr, array_keys($variableHolidays))) {
            foreach ($variableHolidays[$yr] as $value) {
                $daying[] = $value;
            }
        }
        return $daying;
    }

    private function setHolidaysEasterBetween2010and2015()
    {
        $variableHolidays = [

            2010 => [
                mktime(0, 0, 0, 4, 5, date('Y', $lngDate)),
                mktime(0, 0, 0, 5, 24, date('Y', $lngDate))
            ],
            2011 => [
                mktime(0, 0, 0, 4, 25, date('Y', $lngDate)),
                mktime(0, 0, 0, 6, 13, date('Y', $lngDate))
            ],
            2012 => [
                mktime(0, 0, 0, 4, 16, date('Y', $lngDate)),
                mktime(0, 0, 0, 6, 04, date('Y', $lngDate))
            ],
            2013 => [
                mktime(0, 0, 0, 5, 6, date('Y', $lngDate)),
                mktime(0, 0, 0, 6, 24, date('Y', $lngDate))
            ],
            2014 => [
                mktime(0, 0, 0, 4, 21, date('Y', $lngDate)),
                mktime(0, 0, 0, 6, 09, date('Y', $lngDate))
            ],
            2015 => [
                mktime(0, 0, 0, 4, 13, date('Y', $lngDate)),
                mktime(0, 0, 0, 6, 1, date('Y', $lngDate))
            ]
        ];
        $daying           = [];
        $yr               = date('Y', $lngDate);
        if (in_array($yr, array_keys($variableHolidays))) {
            foreach ($variableHolidays[$yr] as $value) {
                $daying[] = $value;
            }
        }
        return $daying;
    }

    /**
     * returns working days in a given month
     *
     * @param date $lngDate
     * @param int $include_easter
     * @return int
     */
    protected function setWorkingDaysInMonth($lngDate, $include_easter)
    {
        $days_in_given_month = round((mktime(0, 0, 0, date('m', $lngDate) + 1, 1, date('Y', $lngDate)) - mktime(0, 0, 0, date('m', $lngDate), 1, date('Y', $lngDate))) / (60 * 60 * 24), 0);
        $tmp_value           = 0;
        for ($counter = 1; $counter <= $days_in_given_month; $counter++) {
            $current_day = mktime(0, 0, 0, date('m', $lngDate), $counter, date('Y', $lngDate));
            if ((!in_array($current_day, $this->setHolidays($lngDate, $include_easter))) && (strftime('%w', $current_day) != 0) && (strftime('%w', $current_day) != 6)) {
                $tmp_value += 1;
            }
        }
        return $tmp_value;
    }
}
