<?php

/*
 * The MIT License
 *
 * Copyright 2018 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace danielgp\salariu;

/**
 * Description of Taxation
 *
 * @author E303778
 */
trait TaxationHealth
{

    protected $txLvl;

    /**
     * CAS
     *
     * */
    private function setHealthFundTax($lngDate, $lngBrutto, $nPercentages, $nValues) {
        $this->txLvl['casP']      = $this->setValuesFromJson($lngDate, $nPercentages);
        $this->txLvl['casP_base'] = $this->setHealthFndTxBs($lngDate, $lngBrutto, $nValues);
        $nReturn                  = $this->txLvl['casP_base'] * $this->txLvl['casP'] / 100;
        if ($lngDate > 20060701) {
            $nReturn = ceil($nReturn / pow(10, 4)) * pow(10, 4);
        }
        $this->txLvl['cas'] = round($nReturn, 0);
    }

    /**
     * baza CAS
     *
     * http://www.lapensie.com/forum/salariul-mediu-brut.php
     * */
    private function setHealthFndTxBs($lngDate, $lngBrutto, $nValues) {
        $crtValues = $nValues[substr($lngDate, 0, 4)];
        $base      = min($lngBrutto, $crtValues['Multiplier'] * $crtValues['Monthly Average Salary']);
        if ($lngDate >= 20170201) {
            $base = $lngBrutto;
        }
        if (array_key_exists('Month Secondary Value', $crtValues)) {
            if (substr($lngDate, 4, 2) >= $crtValues['Month Secondary Value']) {
                $base = min($lngBrutto, $crtValues['Multiplier'] * $crtValues['Monthly Average Salary Secondary']);
            }
        }
        return $base;
    }

    /**
     * Sanatate
     * */
    protected function setHealthTax($lngDate, $lngBrutto, $nPercentages, $nValues) {
        $this->txLvl['sntP'] = $this->setValuesFromJson($lngDate, $nPercentages);
        $nReturn             = round($lngBrutto * $this->txLvl['sntP'] / 100, 0);
        if ($lngDate >= 20170101) {
            $this->txLvl['sntP_base'] = $this->setHealthFndTxBs($lngDate, $lngBrutto, $nValues);
            $nReturn                  = round($this->txLvl['sntP_base'] * $this->txLvl['sntP'] / 100, 0);
        }
        $this->txLvl['snt'] = (($lngDate > 20060701) ? round($nReturn, -4) : $nReturn);
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
