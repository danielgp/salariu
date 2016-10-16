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
            case 2016:
                if ($sPersons <= 3) {
                    $nReturn = 3500000 + ($sPersons * 1000000);
                } elseif ($sPersons > 3) {
                    $nReturn = 8000000;
                }
                if ($lngBrutto >= 30000000) {
                    $nReturn = 0;
                } elseif ($lngBrutto > 15000000) {
                    $nReturn = $nReturn * (1 - ($lngBrutto - 15000000) / 15000000);
                }
                break;
            default:
                if ($sPersons <= 3) {
                    $nReturn = 2500000 + ($sPersons * 1000000);
                } elseif ($sPersons > 3) {
                    $nReturn = 6500000;
                }
                if ($lngBrutto >= 30000000) {
                    $nReturn = 0;
                } elseif ($lngBrutto > 10000000) {
                    $nReturn = $nReturn * (1 - ($lngBrutto - 10000000) / 10000000);
                }
                break;
        }
        if ($lngDate >= mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = round($nReturn, -4);
        }
        return $nReturn;
    }
}
