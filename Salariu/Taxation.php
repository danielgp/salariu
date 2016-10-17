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
 * Description of Taxation
 *
 * @author E303778
 */
trait Taxation
{

    /**
     * CAS
     *
     * */
    protected function setHealthFundTax($lngDate, $lngBrutto, $nPercentages, $nValues)
    {
        $prcntg  = $this->setValuesFromJson($lngDate, $nPercentages);
        $nReturn = round($this->setHealthFundTaxBase($lngDate, $lngBrutto, $nValues) * $prcntg / 100, 0);
        if ($lngDate > mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = ceil($nReturn / pow(10, 4)) * pow(10, 4);
        }
        return $nReturn;
    }

    /**
     * baza CAS
     *
     * http://www.lapensie.com/forum/salariul-mediu-brut.php
     * */
    private function setHealthFundTaxBase($lngDate, $lngBrutto, $nValues)
    {
        $crtValues = $nValues[date('Y', $lngDate)];
        $base      = min($lngBrutto, $crtValues['Multiplier'] * $crtValues['Monthly Average Salary']);
        if (array_key_exists('Month Secondary Value', $crtValues)) {
            if (date('n', $lngDate) >= $crtValues['Month Secondary Value']) {
                $base = min($lngBrutto, $crtValues['Multiplier'] * $crtValues['Monthly Average Salary Secondary']);
            }
        }
        return $base;
    }

    /**
     * Sanatate
     * */
    protected function setHealthTax($lngDate, $lngBrutto, $nPercentages)
    {
        $prcntg  = $this->setValuesFromJson($lngDate, $nPercentages);
        $nReturn = round($lngBrutto * $prcntg / 100, 0);
        if ($lngDate > mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = round($nReturn, -4);
        }
        return $nReturn;
    }

    /**
     * Impozit pe salariu
     * */
    protected function setIncomeTax($lngDate, $lngTaxBase)
    {
        switch (date('Y', $lngDate)) {
            case 2001:
                $nReturn = $this->setIncomeTax2001($lngDate, $lngTaxBase);
                break;
            case 2002:
                $nReturn = $this->setIncomeTax2002($lngTaxBase);
                break;
            case 2003:
                $nReturn = $this->setIncomeTax2003($lngTaxBase);
                break;
            case 2004:
                $nReturn = $this->setIncomeTax2004($lngTaxBase);
                break;
            default:
                $nReturn = $lngTaxBase * 16 / 100;
                break;
        }
        if ($lngDate > mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = round($nReturn, -4);
        }
        return $nReturn;
    }

    /**
     * Impozit pe salariu
     * */
    private function setIncomeTax2001($lngDate, $lngTaxBase)
    {
        $mnth = date('n', $lngDate);
        if ($mnth <= 6) {
            $nReturn = 1822070 + ($lngTaxBase - 6867000 ) * 40 / 100;
            if ($lngTaxBase <= 1259000) {
                $nReturn = $lngTaxBase * 18 / 100;
            } elseif ($lngTaxBase <= 3090000) {
                $nReturn = 226620 + ($lngTaxBase - 1259000 ) * 23 / 100;
            } elseif ($lngTaxBase <= 4921000) {
                $nReturn = 647750 + ($lngTaxBase - 3090000 ) * 28 / 100;
            } elseif ($lngTaxBase <= 6867000) {
                $nReturn = 1160430 + ($lngTaxBase - 4921000 ) * 34 / 100;
            }
        } elseif ($mnth <= 9) {
            $nReturn = 2109940 + ($lngTaxBase - 7952000 ) * 40 / 100;
            if ($lngTaxBase <= 1458000) {
                $nReturn = $lngTaxBase * 18 / 100;
            } elseif ($lngTaxBase <= 3578000) {
                $nReturn = 262440 + ($lngTaxBase - 1458000 ) * 23 / 100;
            } elseif ($lngTaxBase <= 5699000) {
                $nReturn = 750040 + ($lngTaxBase - 3578000 ) * 28 / 100;
            } elseif ($lngTaxBase <= 7952000) {
                $nReturn = 1343920 + ($lngTaxBase - 5699000 ) * 34 / 100;
            }
        } elseif ($mnth > 9) {
            $nReturn = 2231000 + ($lngTaxBase - 8400000 ) * 40 / 100;
            if ($lngTaxBase <= 1500000) {
                $nReturn = $lngTaxBase * 18 / 100;
            } elseif ($lngTaxBase <= 3800000) {
                $nReturn = 270000 + ($lngTaxBase - 1500000 ) * 23 / 100;
            } elseif ($lngTaxBase <= 6000000) {
                $nReturn = 799000 + ($lngTaxBase - 3800000 ) * 28 / 100;
            } elseif ($lngTaxBase <= 8400000) {
                $nReturn = 1415000 + ($lngTaxBase - 6000000 ) * 34 / 100;
            }
        }
        return $nReturn;
    }

    private function setIncomeTax2002($lngTaxBase)
    {
        $nReturn = 2710000 + ($lngTaxBase - 10200000 ) * 40 / 100;
        if ($lngTaxBase <= 1800000) {
            $nReturn = $lngTaxBase * 18 / 100;
        } elseif ($lngTaxBase <= 4600000) {
            $nReturn = 324000 + ($lngTaxBase - 1800000 ) * 23 / 100;
        } elseif ($lngTaxBase <= 7300000) {
            $nReturn = 968000 + ($lngTaxBase - 4600000 ) * 28 / 100;
        } elseif ($lngTaxBase <= 10200000) {
            $nReturn = 1724000 + ($lngTaxBase - 7300000 ) * 34 / 100;
        }
        return $nReturn;
    }

    private function setIncomeTax2003($lngTaxBase)
    {
        $nReturn = 3081000 + ($lngTaxBase - 11600000 ) * 40 / 100;
        if ($lngTaxBase <= 2100000) {
            $nReturn = ($lngTaxBase * 18) / 100;
        } elseif ($lngTaxBase <= 5200000) {
            $nReturn = 324000 + ($lngTaxBase - 2100000 ) * 23 / 100;
        } elseif ($lngTaxBase <= 8300000) {
            $nReturn = 1091000 + ($lngTaxBase - 5200000 ) * 28 / 100;
        } elseif ($lngTaxBase <= 11600000) {
            $nReturn = 1959000 + ($lngTaxBase - 8300000 ) * 34 / 100;
        }
        return $nReturn;
    }

    private function setIncomeTax2004($lngTaxBase)
    {
        $nReturn = 3452000 + ($lngTaxBase - 13000000 ) * 40 / 100;
        if ($lngTaxBase <= 2400000) {
            $nReturn = ($lngTaxBase * 18) / 100;
        } elseif ($lngTaxBase <= 5800000) {
            $nReturn = 432000 + ($lngTaxBase - 2400000 ) * 23 / 100;
        } elseif ($lngTaxBase <= 9300000) {
            $nReturn = 1214000 + ($lngTaxBase - 5800000 ) * 28 / 100;
        } elseif ($lngTaxBase <= 13000000) {
            $nReturn = 2194000 + ($lngTaxBase - 9300000 ) * 34 / 100;
        }
        return $nReturn;
    }

    /**
     * Somaj
     * */
    protected function setUnemploymentTax($lngDate, $lngBase)
    {
        $yrDate  = date('Y', $lngDate);
        $nReturn = 0.5;
        if ($yrDate <= 2007) {
            $nReturn = 1;
        }
        $nReturn = round($lngBase * $nReturn / 100, 0);
        if ($lngDate > mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = round($nReturn, -4);
        }
        return $nReturn;
    }

    /**
     * Media zilelor lucratoare (pt. calcularea suplimentarelor)
     * astfel incat acestea sa nu fie mai valoroase sau nu functie
     * de numarul zilelor din luna respectiva
     *
     * @param date $lngDate
     * @return number
     */
    protected function setMonthlyAverageWorkingHours($lngDate, $stdAvgWrkngHrs, $bCEaster = false)
    {
        $nReturn = $stdAvgWrkngHrs[date('Y', $lngDate)];
        if ($bCEaster) {
            $nReturn = ($nReturn * 12 - 8) / 12;
        }
        return $nReturn;
    }

    private function setValuesFromJson($lngDate, $nValues)
    {
        $crtValues = $nValues[date('Y', $lngDate)];
        $nReturn   = $crtValues['Value'];
        if (array_key_exists('Month Secondary Value', $crtValues)) {
            if (date('n', $lngDate) >= $crtValues['Month Secondary Value']) {
                $nReturn = $crtValues['Secondary Value'];
            }
        }
        return $nReturn;
    }
}
