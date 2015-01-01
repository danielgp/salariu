<?php

require_once 'view.class.php';
/**
 * Brain page
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 1.0
 * @build 20090519
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @license GNU General Public License (GPL)
 */

/**
 * Functions used to handle calculations
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 1.0.1
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @since 1.0.0
 */
class RomanianSalary extends BasicView
{

    public function getValues($longBase)
    {
        $aReturn['cas']      = $this->setHealthFundTax($_REQUEST['ym'], $longBase);
        $aReturn['sanatate'] = $this->setHealthTax($_REQUEST['ym'], $longBase);
        if ($_REQUEST['ym'] < mktime(0, 0, 0, 1, 1, 2008)) {
            $aReturn['somaj'] = $this->setUnemploymentTax($_REQUEST['ym'], $_REQUEST['sn']);
        } else {
            $aReturn['somaj'] = $this->setUnemploymentTax($_REQUEST['ym'], $longBase);
        }
        $aReturn['ba'] = $this->setFoodTicketsValue($_REQUEST['ym']) * ($this->setWorkingDaysInMonth($_REQUEST['ym'], $_REQUEST['pc']) - $_REQUEST['zfb']);
        $rest          = $longBase - $aReturn['cas'] - $aReturn['sanatate'] - $aReturn['somaj'] - $this->setPersonalDeduction($_REQUEST['ym'], $longBase, $_REQUEST['pi']);
        if ($_REQUEST['ym'] >= mktime(0, 0, 0, 7, 1, 2010)) {
            $rest += round($aReturn['ba'], -4);
        }
        $aReturn['gbns'] = $_REQUEST['gbns'] * pow(10, 4);
        if ($_REQUEST['ym'] >= mktime(0, 0, 0, 10, 1, 2010)) {
            $rest += round($aReturn['gbns'], -4);
        }
        $aReturn['impozit'] = $this->setIncomeTax($_REQUEST['ym'], $rest);
        $aReturn['zile']    = $this->setWorkingDaysInMonth($_REQUEST['ym'], $_REQUEST['pc']);
        return $aReturn;
    }

    public function getOvertimes()
    {
        switch ($_REQUEST['pc']) {
            case 0:
                $m = $this->setMonthlyAverageWorkingHours($_REQUEST['ym'], true);
                break;
            case 1:
                $m = $this->setMonthlyAverageWorkingHours($_REQUEST['ym']);
                break;
        }
        $aReturn['os175'] = round($_REQUEST['os175'] * 1.75 * $_REQUEST['sn'] / $m, 0);
        $aReturn['os200'] = round($_REQUEST['os200'] * 2 * $_REQUEST['sn'] / $m, 0);
        return $aReturn;
    }

    /**
     * Media zilelor lucratoare (pt. calcularea suplimentarelor)
     * astfel incat acestea sa nu fie mai valoroase sau nu functie
     * de numarul zilelor din luna respectiva
     *
     * @param date $lngDate
     * @return number
     */
    private function setMonthlyAverageWorkingHours($lngDate, $bCEaster = false)
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

    /**
     * Tichete de alimente
     * */
    private function setFoodTicketsValue($lngDate)
    {
        switch (date('Y', $lngDate)) {
            case 2001:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                        $nReturn = 28800;
                        break;
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                        $nReturn = 34000;
                        break;
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $nReturn = 41000;
                        break;
                }
                break;
            case 2002:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                        $nReturn = 41000;
                        break;
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                        $nReturn = 45000;
                        break;
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $nReturn = 50000;
                        break;
                }
                break;
            case 2003:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                        $nReturn = 50000;
                        break;
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                        $nReturn = 53000;
                        break;
                    case 10:
                    case 11:
                    case 12:
                        $nReturn = 58000;
                        break;
                }
                break;
            case 2004:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                        $nReturn = 58000;
                        break;
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                        $nReturn = 61000;
                        break;
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $nReturn = 65000;
                        break;
                }
                break;
            case 2005:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                        $nReturn = 65000;
                        break;
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                        $nReturn = 68000;
                        break;
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $nReturn = 70000;
                        break;
                }
                break;
            case 2006:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                        $nReturn = 70000;
                        break;
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                        $nReturn = 71500;
                        break;
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $nReturn = 74100;
                        break;
                }
                break;
            case 2007:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                        $nReturn = 74100;
                        break;
                    case 8:
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $nReturn = 75600;
                        break;
                }
            case 2008:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                        $nReturn = 75600;
                        break;
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                        $nReturn = 78800;
                        break;
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $nReturn = 83100;
                        break;
                }
            case 2009:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                        $nReturn = 83100;
                        break;
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                        $nReturn = 84800;
                        break;
                    case 9:
                    case 10:
                    case 11:
                    case 12:
                        $nReturn = 87200;
                        break;
                }
                break;
            case 2010:
                $nReturn = 87200;
                break;
            case 2011:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                        $nReturn = 87200;
                        break;
                    default:
                        $nReturn = 90000;
                        break;
                }
                break;
            case 2012:
                $nReturn = 90000;
                break;
            case 2013:
                if (date('n', $lngDate) < 5) {
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
     * Sanatate
     * */
    private function setHealthTax($lngDate, $lngBrutto)
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
     * CAS
     * */
    private function setHealthFundTax($lngDate, $lngBrutto)
    {
        // http://www.lapensie.com/forum/salariul-mediu-brut.php
        switch (date('Y', $lngDate)) {
            case 2001 :
                $base = min($lngBrutto, 3 * 4148653);
                break;
            case 2002 :
                $base = min($lngBrutto, 3 * 5582000);
                break;
            case 2003 :
                $base = min($lngBrutto, 5 * 6962000);
                break;
            case 2004:
                $base = min($lngBrutto, 5 * 7682000);
                break;
            case 2005:
                if (date('n', $lngDate) <= 6) {
                    $base = min($lngBrutto, 5 * 9211000);
                } else {
                    $base = min($lngBrutto, 5 * 9210000);
                }
                break;
            case 2006: $base = min($lngBrutto, 5 * 10770000);
                break;
            case 2007:
                $base = min($lngBrutto, 5 * 12700000);
                break;
            case 2008:
            case 2009:
            case 2010: //1836
                $base = $lngBrutto;
                break;
            case 2011:
                $base = min($lngBrutto, 5 * 20220000);
                break;
            case 2012:
                $base = min($lngBrutto, 5 * 21170000);
                break;
            case 2013:
                $base = min($lngBrutto, 5 * 22230000);
                break;
            case 2014:
                $base = min($lngBrutto, 5 * 22980000);
                break;
            case 2015:
                $base = min($lngBrutto, 5 * 23820000);
                break;
            default:
                $base = $lngBrutto;
                break;
        }
        switch (date('Y', $lngDate)) {
            case 2001:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                    case 3:
                        $nReturn = 5;
                        break;
                    default:
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
                switch (date('n', $lngDate)) {
                    case 1:
                        $nReturn = 9.5;
                        break;
                    default:
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
            default :
                $nReturn = 0;
                break;
        }
        $nReturn = round($base * $nReturn / 100, 0);
        if ($lngDate > mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = round($nReturn, -4);
        }
        return $nReturn;
    }

    /**
     * Impozit pe salariu
     * */
    private function setIncomeTax($lngDate, $lngTaxBase)
    {
        switch (date('Y', $lngDate)) {
            case 2001:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5 :
                    case 6:
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
                        break;
                    case 7:
                    case 8 :
                    case 9:
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
                        break;
                    default:
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
                        break;
                }
                break;
            case 2002 :
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
                break;
            case 2003:
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
                break;
            case 2004:
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
     * List of legal holidays
     *
     * @param date $lngDate
     * @param int $include_easter
     * @return array
     */
    private function setHolidays($lngDate, $include_easter)
    {
        $counter           = 0;
        $daying [$counter] = mktime(0, 0, 0, 1, 1, date('Y', $lngDate));
// January 1st
        $counter++;
        $daying[$counter]  = mktime(0, 0, 0, 1, 2, date('Y', $lngDate));
// January 2nd
        if ($include_easter == 0) {
            $counter++;
            if (date('Y', $lngDate) == '2005') {
// in Windows returns a faulty day so I treated special
                $daying[$counter] = mktime(0, 0, 0, 3, 27, 2005);
// Easter 1st day
                $counter++;
                $daying[$counter] = mktime(0, 0, 0, 3, 28, 2005);
// Easter 2nd day
            } else {
                $daying[$counter] = mktime(0, 0, 0, date('m'
                        , easter_date(date('Y', $lngDate))), date('j'
                        , easter_date(date('Y', $lngDate))), date('Y'
                        , easter_date(date('Y', $lngDate))));  // Easter 1st day
                $counter++;
                $daying[$counter] = mktime(0, 0, 0, date('m'
                        , easter_date(date('Y', $lngDate))), date('j'
                        , easter_date(date('Y', $lngDate))) + 1, date('Y'
                        , easter_date(date('Y', $lngDate)))); // Easter 2nd day
            }
        }
        $counter++;
        $daying[$counter]  = mktime(0, 0, 0, 5, 1, date('Y', $lngDate));
// May 1st
        $counter++;
        $daying[$counter]  = mktime(0, 0, 0, 12, 1, date('Y', $lngDate));
// Romanian National Day
        $counter++;
        $daying [$counter] = mktime(0, 0, 0, 12, 25, date('Y', $lngDate));
// December 25th
        $counter++;
        $daying[$counter]  = mktime(0, 0, 0, 12, 26, date('Y', $lngDate));
// December 26th
        $counter++;
        switch (date('Y', $lngDate)) {
            case '2003':
                $daying[$counter]  = mktime(0, 0, 0, 4, 26, date('Y', $lngDate));
// Easter 1st day according to Romanian calendar
                $counter++;
                $daying[$counter]  = mktime(0, 0, 0, 4, 27, date('Y', $lngDate));
// Easter 2nd day according to Romanian calendar
                break;
            case '2004':
                $daying[$counter]  = mktime(0, 0, 0, 4, 9, date('Y', $lngDate));
// NATO Day according to Romanian calendar
                break;
            case '2005':
                $daying[$counter]  = mktime(0, 0, 0, 5, 2, date('Y', $lngDate));
                /**
                 * Easter 2nd day according to Romanian calendar
                 * (1st Day it is a Legal Holiday anyway)
                 */ break;
            case '2006':
                $daying[$counter]  = mktime(0, 0, 0, 4, 24, date('Y', $lngDate));
                /**
                 * Easter 2nd day according to Romanian calendar
                 * (1st Day it is a Legal Holiday anyway)
                 */ break;
            case '2007':
                $daying[$counter]  = mktime(0, 0, 0, 4, 9, date('Y', $lngDate));
                /**
                 * Easter 2nd day according to Romanian calendar
                 * (1st Day it is a Legal Holiday anyway)
                 */ break;
            case '2008':
                $daying[$counter]  = mktime(0, 0, 0, 4, 28, date('Y', $lngDate));
                /**
                 * Easter 2nd day according to Romanian calendar
                 * (1st Day it is a Legal Holiday anyway)
                 */ break;
            case '2009':
                $daying[$counter]  = mktime(0, 0, 0, 4, 20, date('Y', $lngDate));
                /**
                 * Easter 2nd day according to Romanian calendar
                 * (1st Day it is a Legal Holiday anyway)
                 */ $counter++;
                $daying[$counter]  = mktime(0, 0, 0, 6, 8, date('Y', $lngDate));
// Rusalii
                break;
            case '2010':
                $daying[$counter]  = mktime(0, 0, 0, 4, 5, date('Y', $lngDate));
                /**
                 * Easter 2nd day according to Romanian calendar
                 * (1st Day it is a Legal Holiday anyway)
                 */ $counter++;
                $daying[$counter]  = mktime(0, 0, 0, 5, 24, date('Y', $lngDate));
// Rusalii
                break;
            case '2011':
                $daying[$counter]  = mktime(0, 0, 0, 4, 25, date('Y', $lngDate));
                /**
                 * Easter 2nd day according to Romanian calendar
                 * (1st Day it is a Legal Holiday anyway)
                 */ $counter++;
                $daying [$counter] = mktime(0, 0, 0, 6, 13, date('Y', $lngDate));
// Rusalii
                break;
            case '2012':
                $daying[$counter]  = mktime(0, 0, 0, 4, 16, date('Y', $lngDate));
                $counter++;
                $daying [$counter] = mktime(0, 0, 0, 6, 04, date('Y', $lngDate));
                break;
            case '2013':
                $daying[$counter]  = mktime(0, 0, 0, 5, 6, date('Y', $lngDate));
                $counter++;
                $daying [$counter] = mktime(0, 0, 0, 6, 24, date('Y', $lngDate));
                break;
            case '2014':
                $daying[$counter]  = mktime(0, 0, 0, 4, 21, date('Y', $lngDate));
                $counter++;
                $daying [$counter] = mktime(0, 0, 0, 6, 09, date('Y', $lngDate));
                break;
            case '2015':
                $daying[$counter]  = mktime(0, 0, 0, 4, 13, date('Y', $lngDate));
                $counter++;
                $daying [$counter] = mktime(0, 0, 0, 6, 1, date('Y', $lngDate));
                break;
        }
        if (date('Y', $lngDate) >= 2009) {
// St. Marry
            $counter++;
            $daying[$counter] = mktime(0, 0, 0, 8, 15, date('Y', $lngDate));
        }
        if (date('Y', $lngDate) >= 2012) {
// St. Andrew
            $counter++;
            $daying[$counter] = mktime(0, 0, 0, 11, 30, date('Y', $lngDate));
        }
        return $daying;
    }

    /**
     * Deducere personala
     * */
    private function setPersonalDeduction($lngDate, $lngBrutto, $sPersons)
    {
        switch (date('Y', $lngDate)) {
            case 2001:
                switch (date('n', $lngDate)) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                        $nReturn = 1099000;
                        break;
                    case 7:
                    case 8:
                    case 9:
                        $nReturn = 1273000;
                        break;
                    default:
                        $nReturn = 1300000;
                        break;
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
                    switch ($sPersons) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                            $nReturn = 2500000 + ($sPersons * 1000000);
                            break;
                        default:
                            $nReturn = 6500000;
                    }
                    if ($lngBrutto > 10000000) {
                        $nReturn = $nReturn *
                            (1 - ($lngBrutto - 10000000) / 20000000);
                    }
                }
                break;
        }
        return $nReturn;
    }

    /**
     * Somaj
     * */
    private function setUnemploymentTax($lngDate, $lngBase)
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
        } $nReturn = round($lngBase * $nReturn / 100, 0);
        if ($lngDate > mktime(0, 0, 0, 7, 1, 2006)) {
            $nReturn = round($nReturn, -4);
        }
        return $nReturn;
    }

    /**
     * returns working days in a given month
     *
     * @param date $lngDate
     * @param int $include_easter
     * @return int
     */
    public function setWorkingDaysInMonth($lngDate, $include_easter)
    {
        $days_in_given_month = round((mktime(0, 0, 0, date('m', $lngDate) + 1, 1, date('Y', $lngDate)) - mktime(0, 0, 0, date('m', $lngDate), 1, date('Y', $lngDate))) / (60 * 60 * 24), 0);
        $tmp_value           = 0;
        for ($counter = 1; $counter <= $days_in_given_month; $counter++) {
            $current_day = mktime(0, 0, 0, date('m', $lngDate), $counter, date('Y', $lngDate));
            if ((!in_array($current_day, $this->setHolidays($lngDate, $include_easter))) && (strftime('%w', $current_day) != 0) && (strftime('%w', $current_day) != 6)) {
                $tmp_value += 1;
            }
        }
        return $tmp_value;
    }
}
