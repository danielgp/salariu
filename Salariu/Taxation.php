<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Daniel Popiniuc
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

    private $txLvl;

    /**
     * CAS
     *
     * */
    private function setHealthFundTax($lngDate, $lngBrutto, $nPercentages, $nValues)
    {
        $this->txLvl['casP']      = $this->setValuesFromJson($lngDate, $nPercentages);
        $this->txLvl['casP_base'] = $this->setHealthFndTxBs($lngDate, $lngBrutto, $nValues);
        $nReturn                  = $this->txLvl['casP_base'] * $this->txLvl['casP'] / 100;
        if ($lngDate > mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = ceil($nReturn / pow(10, 4)) * pow(10, 4);
        }
        $this->txLvl['cas'] = round($nReturn, 0);
    }

    /**
     * baza CAS
     *
     * http://www.lapensie.com/forum/salariul-mediu-brut.php
     * */
    private function setHealthFndTxBs($lngDate, $lngBrutto, $nValues)
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
    protected function setHealthTax($lngDate, $lngBrutto, $nPercentages, $nValues)
    {
        $this->txLvl['sntP'] = $this->setValuesFromJson($lngDate, $nPercentages);
        $nReturn             = round($lngBrutto * $this->txLvl['sntP'] / 100, 0);
        if ($lngDate >= mktime(0, 0, 0, 1, 1, 2017)) {
            $this->txLvl['sntP_base'] = $this->setHealthFndTxBs($lngDate, $lngBrutto, $nValues);
            $nReturn                  = round($this->txLvl['sntP_base'] * $this->txLvl['sntP'] / 100, 0);
        }
        $this->txLvl['snt'] = (($lngDate > mktime(0, 0, 0, 7, 1, 2006)) ? round($nReturn, -4) : $nReturn);
    }

    /**
     * Impozit pe salariu
     * */
    protected function setIncomeTax($lngDate, $lngTaxBase, $nValues)
    {
        $this->txLvl['inTaxP'] = '16';
        $nReturn               = $lngTaxBase * 16 / 100;
        $yrDate                = date('Y', $lngDate);
        if (in_array($yrDate, [2002, 2003, 2004])) {
            $nReturn = $this->setIncomeTaxFromJson($lngTaxBase, $nValues[$yrDate]);
        } elseif ($yrDate == 2001) {
            $nReturn = $this->setIncomeTax2001($lngDate, $lngTaxBase, $nValues);
        }
        return (($lngDate >= mktime(0, 0, 0, 7, 1, 2006)) ? round($nReturn, -4) : $nReturn);
    }

    /**
     * Impozit pe salariu
     * */
    private function setIncomeTax2001($lngDate, $lngTaxBase, $nValues)
    {
        $nReturn = 0;
        $mnth    = date('n', $lngDate);
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
                $sLbl                  = [
                    'BDP' => $nValues[$counter]['Base Deducted Percentage'],
                    'BDV' => $nValues[$counter]['Base Deduction Value'],
                    'TFV' => $nValues[$counter]['Tax Free Value'],
                ];
                $nReturn               = $sLbl['TFV'] + ($lngTaxBase - $sLbl['BDV']) * $sLbl['BDP'] / 100;
                $this->txLvl['inTaxP'] = 'fx+' . $sLbl['BDP'];
                $counter               = $howMany;
            }
        }
        return $nReturn;
    }

    /**
     * Somaj
     * */
    protected function setUnemploymentTax($lngDate, $lngBase)
    {
        $yrDate              = date('Y', $lngDate);
        $this->txLvl['smjP'] = 0.5;
        if ($yrDate <= 2007) {
            $this->txLvl['smjP'] = 1;
        }
        $nReturn            = round($lngBase * $this->txLvl['smjP'] / 100, 0);
        $this->txLvl['smj'] = (($lngDate >= mktime(0, 0, 0, 7, 1, 2006)) ? round($nReturn, -4) : $nReturn);
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
