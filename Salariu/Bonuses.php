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
 * Description of Bonuses
 *
 * @author E303778
 */
trait Bonuses
{

    /**
     * Tichete de alimente
     * */
    protected function setFoodTicketsValue($lngDate)
    {
        $historyValue = [
            $this->setFoodTicketsValueBetween2001and2005($lngDate),
            $this->setFoodTicketsValueBetween2006and2009($lngDate),
            $this->setFoodTicketsValueBetween2011and2015($lngDate)
        ];
        return array_sum($historyValue);
    }

    /**
     * Tichete de alimente
     * */
    private function setFoodTicketsValueBetween2001and2005($lngDate)
    {
        $mnth    = date('n', $lngDate);
        $nReturn = 0;
        switch (date('Y', $lngDate)) {
            case 2001:
                if ($mnth <= 2) {
                    $nReturn = 28800;
                } elseif ($mnth <= 8) {
                    $nReturn = 34000;
                } else {
                    $nReturn = 41000;
                }
                break;
            case 2002:
                if ($mnth <= 2) {
                    $nReturn = 41000;
                } elseif ($mnth <= 8) {
                    $nReturn = 45000;
                } else {
                    $nReturn = 50000;
                }
                break;
            case 2003:
                if ($mnth <= 2) {
                    $nReturn = 50000;
                } elseif ($mnth <= 9) {
                    $nReturn = 53000;
                } else {
                    $nReturn = 58000;
                }
                break;
            case 2004:
                if ($mnth <= 2) {
                    $nReturn = 58000;
                } elseif ($mnth <= 8) {
                    $nReturn = 61000;
                } else {
                    $nReturn = 65000;
                }
                break;
            case 2005:
                if ($mnth <= 2) {
                    $nReturn = 65000;
                } elseif ($mnth <= 8) {
                    $nReturn = 68000;
                } else {
                    $nReturn = 70000;
                }
                break;
        }
        return $nReturn;
    }

    /**
     * Tichete de alimente
     * */
    private function setFoodTicketsValueBetween2006and2009($lngDate)
    {
        $mnth = date('n', $lngDate);
        switch (date('Y', $lngDate)) {
            case 2006:
                if ($mnth <= 2) {
                    $nReturn = 70000;
                } elseif ($mnth <= 8) {
                    $nReturn = 71500;
                } else {
                    $nReturn = 74100;
                }
                break;
            case 2007:
                if ($mnth <= 8) {
                    $nReturn = 74100;
                } else {
                    $nReturn = 75600;
                }
                break;
            case 2008:
                if ($mnth <= 2) {
                    $nReturn = 75600;
                } elseif ($mnth <= 8) {
                    $nReturn = 78800;
                } else {
                    $nReturn = 83100;
                }
                break;
            case 2009:
                if ($mnth <= 2) {
                    $nReturn = 83100;
                } elseif ($mnth <= 8) {
                    $nReturn = 84800;
                } else {
                    $nReturn = 87200;
                }
                break;
            case 2010:
                $nReturn = 87200;
                break;
            default:
                $nReturn = 0;
                break;
        }
        return $nReturn;
    }

    /**
     * Tichete de alimente
     * */
    private function setFoodTicketsValueBetween2011and2015($lngDate)
    {
        $mnth = date('n', $lngDate);
        switch (date('Y', $lngDate)) {
            case 2011:
                if ($mnth <= 2) {
                    $nReturn = 87200;
                } else {
                    $nReturn = 90000;
                }
                break;
            case 2012:
                $nReturn = 90000;
                break;
            case 2013:
                if ($mnth < 5) {
                    $nReturn = 90000;
                } else {
                    $nReturn = 93500;
                }
                break;
            case 2014:
            case 2015:
                $nReturn = 93500;
                break;
            default:
                $nReturn = 0;
                break;
        }
        return $nReturn;
    }

    /**
     * Deducere personala
     * */
    protected function setPersonalDeduction($lngDate, $lngBrutto, $sPersons)
    {
        switch (date('Y', $lngDate)) {
            case 2001:
                $mnth = date('n', $lngDate);
                if ($mnth <= 6) {
                    $nReturn = 1099000;
                } elseif ($mnth <= 9) {
                    $nReturn = 1273000;
                } else {
                    $nReturn = 1300000;
                }
                break;
            case 2002:
                $nReturn = 1600000;
                break;
            case 2003:
                $nReturn = 1800000;
                break;
            case 2004:
                $nReturn = 2000000;
                break;
            default:
                if ($lngBrutto >= 30000000) {
                    $nReturn = 0;
                } else {
                    if ($sPersons <= 3) {
                        $nReturn = 2500000 + ($sPersons * 1000000);
                    } else {
                        $nReturn = 6500000;
                    }
                    if ($lngBrutto > 10000000) {
                        $nReturn = $nReturn * (1 - ($lngBrutto - 10000000) / 20000000);
                    }
                }
                break;
        }
        if ($lngDate >= mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = round($nReturn, -4);
        }
        return $nReturn;
    }
}
