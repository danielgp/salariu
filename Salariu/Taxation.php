<?php

//

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2017 Daniel Popiniuc
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

    private function determineUnemploymentTax($yearMonth, $inMny, $dtR) {
        $intValue   = 0;
        $maxCounter = count($inMny);
        for ($counter = 0; $counter < $maxCounter; $counter++) {
            $crtVal         = $inMny[$counter];
            $crtDV          = \DateTime::createFromFormat('Y-n-j', $crtVal['Year'] . '-' . $crtVal['Month'] . '-01');
            $crtDateOfValue = (int) $crtDV->format('Ymd');
            if (($yearMonth <= $dtR['maximumInt']) && ($yearMonth >= $crtDateOfValue)) {
                $intValue = $crtVal['Percentage'];
                $counter  = $maxCounter;
            }
        }
        return $intValue;
    }

    private function getIncomeTaxBaseAdjustments(\Symfony\Component\HttpFoundation\Request $tCSG, $rest, $inMny) {
        $restFinal    = $rest + round($inMny['Food Tickets Value'], -4);
        $val          = $tCSG->get('gbns');
        $taxMinAmount = 0;
        if ($inMny['inDate'] >= 20160101) {
            $taxMinAmount = 150 * ($val || 0);
        }
        if ($inMny['inDate'] >= 20101001) {
            $restFinal += round(min($val, $val - $taxMinAmount) * pow(10, 4), -4);
        }
        return $restFinal;
    }

    private function getIncomeTaxValue(\Symfony\Component\HttpFoundation\Request $tCSG, $inMny) {
        $rest   = 0;
        $dinish = array_sum($inMny['Deductions']) + round($tCSG->get('afet') * pow(10, 4), -4);
        if ($inMny['lngBase'] > $dinish) {
            $rest = $inMny['lngBase'] - array_sum($inMny['Deductions']) + round($tCSG->get('afet') * pow(10, 4), -4);
        }
        if ($inMny['inDate'] >= 20100701) {
            $rest = $this->getIncomeTaxBaseAdjustments($tCSG, $rest, $inMny);
        }
        return $this->setIncomeTax($inMny['inDate'], $rest, $inMny['Income Tax']);
    }

    /**
     * Impozit pe salariu
     * */
    protected function setIncomeTax($lngDate, $lngTaxBase, $nValues) {
        $yrDate  = (int) substr($lngDate, 0, 4);
        $nReturn = $this->setIncomeTaxFromJson($lngTaxBase, $nValues, $yrDate);
        if ($yrDate == 2001) {
            $nReturn = $this->setIncomeTax2001($lngDate, $lngTaxBase, $nValues);
        }
        return (($lngDate >= 20060701) ? round($nReturn, -4) : $nReturn);
    }

    /**
     * Impozit pe salariu
     * */
    private function setIncomeTax2001($lngDate, $lngTaxBase, $nValues) {
        $mnth    = substr($lngDate, 4, 2);
        $nReturn = $this->setIncomeTaxFromJson($lngTaxBase, $nValues["2001-12"]); // for > 9
        if ($mnth <= 6) {
            $nReturn = $this->setIncomeTaxFromJson($lngTaxBase, $nValues["2001-06"]);
        } elseif ($mnth <= 9) {
            $nReturn = $this->setIncomeTaxFromJson($lngTaxBase, $nValues["2001-09"]);
        }
        return $nReturn;
    }

    private function setIncomeTaxFromJson($lngTaxBase, $nValues, $yrDate) {
        $nReturn = 0;
        $howMany = count($nValues);
        if (array_key_exists('Percentage', $nValues[$yrDate])) {
            $this->txLvl['inTaxP']      = $nValues[$yrDate]['Percentage'];
            $this->txLvl['inTaxP_base'] = $lngTaxBase;
            $nReturn                    = $lngTaxBase * $this->txLvl['inTaxP'] / 100;
        } else {
            for ($counter = 0; $counter <= $howMany; $counter++) {
                $nReturn = $lngTaxBase * $this->txLvl['inTaxP'];
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
        }
        return $nReturn;
    }

    /**
     * Somaj
     * */
    protected function setUnemploymentTax($lngDate, $lngBase, $yearMonth, $aStngs, $dtR) {
        $this->txLvl['smjP'] = $this->determineUnemploymentTax($yearMonth, $aStngs, $dtR);
        $nReturn             = round($lngBase * $this->txLvl['smjP'] / 100, 0);
        $this->txLvl['smj']  = (($lngDate >= 20060701) ? round($nReturn, -4) : $nReturn);
    }

    /**
     * Media zilelor lucratoare (pt. calcularea suplimentarelor)
     * astfel incat acestea sa nu fie mai valoroase sau nu functie
     * de numarul zilelor din luna respectiva
     *
     * @param string $lngDate
     * @param array $stdAvgWrkngHrs
     * @param boolean $bCEaster
     * @return int
     */
    protected function setMonthlyAverageWorkingHours($lngDate, $stdAvgWrkngHrs, $bCEaster = false) {
        $nReturn = $stdAvgWrkngHrs[substr($lngDate, 0, 4)];
        if ($bCEaster) {
            $nReturn = ($nReturn * 12 - 8) / 12;
        }
        return $nReturn;
    }

    private function setValuesFromJson($lngDate, $nValues) {
        $crtValues = $nValues[substr($lngDate, 0, 4)];
        $nReturn   = $crtValues['Value'];
        if (array_key_exists('Month Secondary Value', $crtValues)) {
            if (date('n', $lngDate) >= $crtValues['Month Secondary Value']) {
                $nReturn = $crtValues['Secondary Value'];
            }
        }
        return $nReturn;
    }

}
