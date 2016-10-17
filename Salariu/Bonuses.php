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
     * returns an array with non-standard holidays from a JSON file
     *
     * @param string $fileBaseName
     * @return mixed
     */
    private function readTypeFromJsonFile($fileBaseName)
    {
        $fName       = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $fileBaseName . '.min.json';
        $fJson       = fopen($fName, 'r');
        $jSonContent = fread($fJson, filesize($fName));
        fclose($fJson);
        return json_decode($jSonContent, true);
    }

    /**
     * Tichete de alimente
     * */
    protected function setFoodTicketsValue($lngDate)
    {
        $arrayValues           = $this->readTypeFromJsonFile('static')['Meal Ticket Value'];
        $valueMealTicket       = 0;
        $indexArrayValues      = 0;
        $currentUpperLimitDate = mktime(0, 0, 0, date('n'), 1, date('Y'));
        while (($valueMealTicket === 0)) {
            $crtVal                = $arrayValues[$indexArrayValues];
            $currentLowerLimitDate = mktime(0, 0, 0, $crtVal['Month'], 1, $crtVal['Year']);
            if (($lngDate <= $currentUpperLimitDate) && ($lngDate >= $currentLowerLimitDate)) {
                $valueMealTicket = $crtVal['Value'];
            }
            $currentUpperLimitDate = $currentLowerLimitDate;
            $indexArrayValues++;
        }
        return $valueMealTicket;
    }

    /**
     * Deducere personala
     * */
    protected function setPersonalDeduction($lngDate, $lngBrutto, $sPersons)
    {
        $yrDate  = date('Y', $lngDate);
        $nReturn = 0;
        if ($yrDate >= 2005) {
            $nReturn = $this->setPersonalDeductionComplex2016($sPersons, $lngBrutto, $yrDate);
        } elseif ($yrDate >= 2001) {
            $valuesYearly = [
                2001 => $this->setPersonalDeductionSimple2001($lngDate),
                2002 => 1600000,
                2003 => 1800000,
                2004 => 2000000,
            ];
            $nReturn      = $valuesYearly[$yrDate];
        }
        if ($lngDate >= mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = round($nReturn, -4);
        }
        return $nReturn;
    }

    private function setPersonalDeductionComplex($sPersons, $lngBrutto, $inRule)
    {
        $nDeduction = $inRule['Limit maximum amount'];
        if ($sPersons <= $inRule['Limit persons']) {
            $nDeduction = $inRule['Limit basic amount'] + ($sPersons * $inRule['Limit /person amount']);
        }
        $nReturn = $nDeduction;
        if ($lngBrutto >= $inRule['Limit zero deduction']) {
            $nReturn = 0;
        } elseif ($lngBrutto > $inRule['Reduced deduction']) {
            $nReturn = $nDeduction * (1 - ($lngBrutto - $inRule['Reduced deduction']) / $inRule['Reduced deduction']);
        }
        return $nReturn;
    }

    private function setPersonalDeductionComplex2016($sPersons, $lngBrutto, $yrDate)
    {
        $nValues = [
            2016 => [
                'Limit persons'        => 3,
                'Limit basic amount'   => 3500000,
                'Limit maximum amount' => 8000000,
                'Limit /person amount' => 1000000,
                'Limit zero deduction' => 30000000,
                'Reduced deduction'    => 15000000,
            ],
            2005 => [
                'Limit persons'        => 3,
                'Limit basic amount'   => 2500000,
                'Limit maximum amount' => 6500000,
                'Limit /person amount' => 1000000,
                'Limit zero deduction' => 30000000,
                'Reduced deduction'    => 10000000,
            ],
        ];
        $inRule  = $nValues[2005];
        if ($yrDate == 2016) {
            $inRule = $nValues[2016];
        }
        return $this->setPersonalDeductionComplex($sPersons, $lngBrutto, $inRule);
    }

    private function setPersonalDeductionSimple2001($lngDate)
    {
        $nReturn = 1300000;
        $mnDate  = date('n', $lngDate);
        if ($mnDate <= 6) {
            $nReturn = 1099000;
        } elseif ($mnDate <= 9) {
            $nReturn = 1273000;
        }
        return $nReturn;
    }
}
