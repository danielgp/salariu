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
    protected function setIncomeTax($lngDate, $lngTaxBase, $nValues)
    {
        $nReturn = $lngTaxBase * 16 / 100;
        $yrDate  = date('Y', $lngDate);
        if (in_array($yrDate, [2002, 2003, 2004])) {
            $nReturn = $this->setIncomeTaxFromJson($lngTaxBase, $nValues[$yrDate]);
        } elseif ($yrDate == 2001) {
            $nReturn = $this->setIncomeTax2001($lngDate, $lngTaxBase, $nValues);
        }
        if ($lngDate > mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = round($nReturn, -4);
        }
        return $nReturn;
    }

    /**
     * Impozit pe salariu
     * */
    private function setIncomeTax2001($lngDate, $lngTaxBase, $nValues)
    {
        $mnth = date('n', $lngDate);
        if ($mnth <= 6) {
            $nReturn = $this->setIncomeTaxFromJson($lngTaxBase, $nValues["2001-06"]);
        } elseif ($mnth <= 9) {
            $nReturn = $this->setIncomeTaxFromJson($lngTaxBase, $nValues["2001-09"]);
        } elseif ($mnth > 9) {
            $nReturn = $this->setIncomeTaxFromJson($lngTaxBase, $nValues["2001-12"]);
        }
        return $nReturn;
    }

    private function setIncomeTaxFromJson($lngTaxBase, $nValues)
    {
        $nReturn = 0;
        $howMany = count($nValues);
        for ($counter = 0; $counter <= $howMany; $counter++) {
            if (($lngTaxBase <= $nValues[$counter]['Upper Limit Value'])) {
                $sLbl    = [
                    'BDP' => $nValues[$counter]['Base Deducted Percentage'],
                    'BDV' => $nValues[$counter]['Base Deduction Value'],
                    'TFV' => $nValues[$counter]['Tax Free Value'],
                ];
                $nReturn = $sLbl['TFV'] + ($lngTaxBase - $sLbl['BDV']) * $sLbl['BDP'] / 100;
                $counter = $howMany;
            }
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
