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
 * Description of Bonuses
 *
 */
trait Bonuses
{

    use InputValidation;

    /**
     * Tichete de alimente
     * */
    protected function setFoodTicketsValue($dtR, $lngDate, $aryStngMealTickets)
    {
        $valueMealTicket   = 0;
        $indexArrayValues  = 0;
        $crtUpperLimitDate = (int) $dtR['maximum']->format('Ymd');
        while (($valueMealTicket === 0)) {
            $crtVal            = $aryStngMealTickets[$indexArrayValues];
            $crtLLDate         = \DateTime::createFromFormat('Y-n-j', $crtVal['Year'] . '-' . $crtVal['Month'] . '-1');
            $crtLowerLimitDate = (int) $crtLLDate->format('Ymd');
            if (($lngDate <= $crtUpperLimitDate) && ($lngDate >= $crtLowerLimitDate)) {
                $valueMealTicket = $crtVal['Value'];
            }
            $crtUpperLimitDate = $crtLowerLimitDate;
            $indexArrayValues++;
        }
        return $valueMealTicket;
    }

    /**
     * Deducere personala
     * */
    protected function setPersonalDeduction($lngDate, $lngBrutto, $sPersons, $cValues)
    {
        $yrDate  = substr($lngDate, 0, 4);
        $nReturn = 0;
        if ($yrDate >= 2005) {
            $nReturn = $this->setPersonalDeductionComplex2016($sPersons, $lngBrutto, $yrDate, $cValues);
        } elseif ($yrDate >= 2001) {
            $valuesYearly = [
                2001 => $this->setPersonalDeductionSimple2001($lngDate),
                2002 => 1600000,
                2003 => 1800000,
                2004 => 2000000,
            ];
            $nReturn      = $valuesYearly[$yrDate];
        }
        return (($lngDate >= 20060701) ? round($nReturn, -4) : $nReturn);
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

    private function setPersonalDeductionComplex2016($sPersons, $lngBrutto, $yrDate, $cValues)
    {
        $inRule = $cValues[2005];
        if ($yrDate >= 2016) {
            $inRule = $cValues[2016];
        }
        return $this->setPersonalDeductionComplex($sPersons, $lngBrutto, $inRule);
    }

    private function setPersonalDeductionSimple2001($lngDate)
    {
        $nReturn = 1300000;
        $mnDate  = substr($lngDate, 4, 2);
        if ($mnDate <= 6) {
            $nReturn = 1099000;
        } elseif ($mnDate <= 9) {
            $nReturn = 1273000;
        }
        return $nReturn;
    }
}
