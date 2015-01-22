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
class Taxation extends Bonuses
{

    /**
     * CAS
     *
     * */
    protected function setHealthFundTax($lngDate, $lngBrutto)
    {
        switch (date('Y', $lngDate)) {
            case 2001:
                if (date('n', $lngDate) <= 3) {
                    $nReturn = 5;
                } else {
                    $nReturn = 11.67;
                }
                break;
            case 2002:
                $nReturn = 11.67;
                break;
            case 2003:
            case 2004:
            case 2005:
            case 2006:
            case 2007:
            case 2008:
                $nReturn = 9.5;
                break;
            case 2009:
                if (date('n', $lngDate) == 1) {
                    $nReturn = 9.5;
                } else {
                    $nReturn = 10.5;
                }
                break;
            case 2010:
            case 2011:
            case 2012:
            case 2013:
            case 2014:
            case 2015:
                $nReturn = 10.5;
                break;
            default:
                $nReturn = 0;
                break;
        }
        $nReturn = round($this->setHealthFundTaxBase($lngDate, $lngBrutto) * $nReturn / 100, 0);
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
    private function setHealthFundTaxBase($lngDate, $lngBrutto)
    {
        $yr        = date('Y', $lngDate);
        $baseArray = [
            2001  => min($lngBrutto, 3 * 4148653),
            2002  => min($lngBrutto, 3 * 5582000),
            2003  => min($lngBrutto, 5 * 6962000),
            2004  => min($lngBrutto, 5 * 7682000),
            2006  => $base = min($lngBrutto, 5 * 10770000),
            2007  => min($lngBrutto, 5 * 12700000),
            2008  => min($lngBrutto, 5 * 15500000),
            2009  => min($lngBrutto, 5 * 16930000),
            2010  => min($lngBrutto, 5 * 18360000),
            2011  => min($lngBrutto, 5 * 20220000),
            2012  => min($lngBrutto, 5 * 21170000),
            2013  => min($lngBrutto, 5 * 22230000),
            2014  => min($lngBrutto, 5 * 22980000),
            2015  => min($lngBrutto, 5 * 23820000),
        ];
        if ($yr == 2005) {
            if (date('n', $lngDate) <= 6) {
                $base = min($lngBrutto, 5 * 9211000);
            } else {
                $base = min($lngBrutto, 5 * 9210000);
            }
        } elseif (in_array($yr, array_keys($baseArray))) {
            $base = $baseArray[$yr];
        } else {
            $base = $lngBrutto;
        }
        return $base;
    }

    /**
     * Sanatate
     * */
    protected function setHealthTax($lngDate, $lngBrutto)
    {
        switch (date('Y', $lngDate)) {
            case 2001:
            case 2002:
                $nReturn = 7;
                break;
            case 2003:
            case 2004:
            case 2005:
            case 2006:
            case 2007:
                $nReturn = 6.5;
                break;
            case 2008:
                if (date('n', $lngDate) < 7) {
                    $nReturn = 6.5;
                } else {
                    $nReturn = 5.5;
                }
                break;
            default:
                $nReturn = 5.5;
                break;
        }
        $nReturn = round($lngBrutto * $nReturn / 100, 0);
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
            if ($lngTaxBase <= 1259000) {
                $nReturn = $lngTaxBase * 18 / 100;
            } elseif ($lngTaxBase <= 3090000) {
                $nReturn = 226620 + ($lngTaxBase - 1259000 ) * 23 / 100;
            } elseif ($lngTaxBase <= 4921000) {
                $nReturn = 647750 + ($lngTaxBase - 3090000 ) * 28 / 100;
            } elseif ($lngTaxBase <= 6867000) {
                $nReturn = 1160430 + ($lngTaxBase - 4921000 ) * 34 / 100;
            } else {
                $nReturn = 1822070 + ($lngTaxBase - 6867000 ) * 40 / 100;
            }
        } elseif ($mnth <= 9) {
            if ($lngTaxBase <= 1458000) {
                $nReturn = $lngTaxBase * 18 / 100;
            } elseif ($lngTaxBase <= 3578000) {
                $nReturn = 262440 + ($lngTaxBase - 1458000 ) * 23 / 100;
            } elseif ($lngTaxBase <= 5699000) {
                $nReturn = 750040 + ($lngTaxBase - 3578000 ) * 28 / 100;
            } elseif ($lngTaxBase <= 7952000) {
                $nReturn = 1343920 + ($lngTaxBase - 5699000 ) * 34 / 100;
            } else {
                $nReturn = 2109940 + ($lngTaxBase - 7952000 ) * 40 / 100;
            }
        } else {
            if ($lngTaxBase <= 1500000) {
                $nReturn = $lngTaxBase * 18 / 100;
            } elseif ($lngTaxBase <= 3800000) {
                $nReturn = 270000 + ($lngTaxBase - 1500000 ) * 23 / 100;
            } elseif ($lngTaxBase <= 6000000) {
                $nReturn = 799000 + ($lngTaxBase - 3800000 ) * 28 / 100;
            } elseif ($lngTaxBase <= 8400000) {
                $nReturn = 1415000 + ($lngTaxBase - 6000000 ) * 34 / 100;
            } else {
                $nReturn = 2231000 + ($lngTaxBase - 8400000 ) * 40 / 100;
            }
        }
        return $nReturn;
    }

    private function setIncomeTax2002($lngTaxBase)
    {
        if ($lngTaxBase <= 1800000) {
            $nReturn = $lngTaxBase * 18 / 100;
        } elseif ($lngTaxBase <= 4600000) {
            $nReturn = 324000 + ($lngTaxBase - 1800000 ) * 23 / 100;
        } elseif ($lngTaxBase <= 7300000) {
            $nReturn = 968000 + ($lngTaxBase - 4600000 ) * 28 / 100;
        } elseif ($lngTaxBase <= 10200000) {
            $nReturn = 1724000 + ($lngTaxBase - 7300000 ) * 34 / 100;
        } else {
            $nReturn = 2710000 + ($lngTaxBase - 10200000 ) * 40 / 100;
        }
        return $nReturn;
    }

    private function setIncomeTax2003($lngTaxBase)
    {
        if ($lngTaxBase <= 2100000) {
            $nReturn = ($lngTaxBase * 18) / 100;
        } elseif ($lngTaxBase <= 5200000) {
            $nReturn = 324000 + ($lngTaxBase - 2100000 ) * 23 / 100;
        } elseif ($lngTaxBase <= 8300000) {
            $nReturn = 1091000 + ($lngTaxBase - 5200000 ) * 28 / 100;
        } elseif ($lngTaxBase <= 11600000) {
            $nReturn = 1959000 + ($lngTaxBase - 8300000 ) * 34 / 100;
        } else {
            $nReturn = 3081000 + ($lngTaxBase - 11600000 ) * 40 / 100;
        }
        return $nReturn;
    }

    private function setIncomeTax2004($lngTaxBase)
    {
        if ($lngTaxBase <= 2400000) {
            $nReturn = ($lngTaxBase * 18) / 100;
        } elseif ($lngTaxBase <= 5800000) {
            $nReturn = 432000 + ($lngTaxBase - 2400000 ) * 23 / 100;
        } elseif ($lngTaxBase <= 9300000) {
            $nReturn = 1214000 + ($lngTaxBase - 5800000 ) * 28 / 100;
        } elseif ($lngTaxBase <= 13000000) {
            $nReturn = 2194000 + ($lngTaxBase - 9300000 ) * 34 / 100;
        } else {
            $nReturn = 3452000 + ($lngTaxBase - 13000000 ) * 40 / 100;
        }
        return $nReturn;
    }

    /**
     * Somaj
     * */
    protected function setUnemploymentTax($lngDate, $lngBase)
    {
        switch (date('Y', $lngDate)) {
            case 2001:
            case 2002:
            case 2003:
            case 2004:
            case 2005:
            case 2006:
            case 2007:
                $nReturn = 1;
                break;
            default:
                $nReturn = 0.5;
                break;
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
    protected function setMonthlyAverageWorkingHours($lngDate, $bCEaster = false)
    {
        switch (gmdate('Y', $lngDate)) {
            case 2002:
            case 2003:
                $nReturn = 170;
                break;
            case 2004:
                $nReturn = 172;
                break;
            case 2005:
                $nReturn = 171.33;
                break;
            case 2006:
                $nReturn = 168.66;
                break;
            case 2007:
            case 2008:
                $nReturn = 170;
                break;
            case 2009:
                $nReturn = 169.33;
                break;
            case 2010:
                $nReturn = 170.66;
                break;
            case 2011:
                $nReturn = 169.33;
                break;
            case 2012:
                $nReturn = 168.66;
                break;
            case 2013:
                $nReturn = 168;
                break;
            case 2014:
                $nReturn = 168;
                break;
            case 2015:
                $nReturn = 168.66;
                break;
            default:
                $nReturn = 0;
                break;
        }
        if ($bCEaster) {
            $nReturn = ($nReturn * 12 - 8) / 12;
        }
        return $nReturn;
    }
}
