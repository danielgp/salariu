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

class Salariu
{

    use \danielgp\common_lib\CommonCode;

    private $applicationFlags;
    private $exchangeRateDate;
    private $exchangeRatesDefined;
    private $exchangeRatesValue;

    public function __construct()
    {
        $this->applicationFlags = [
            'available_languages' => [
                'en_US' => 'EN',
                'ro_RO' => 'RO',
            ],
            'default_language'    => 'ro_RO'
        ];
        $this->handleLocalizationSalariu();
        echo $this->setHeaderHtml();
        echo $this->setFormInput();
        if (isset($_REQUEST['ym'])) {
            $this->getExchangeRates();
            echo $this->setFormOutput();
        }
        echo $this->setFooterHtml();
    }

    private function getExchangeRates()
    {
        $this->exchangeRatesDefined = [
            'RON' => [
                'decimals' => 0,
                'locale'   => 'ro_RO',
            ],
            'EUR' => [
                'decimals' => 2,
                'locale'   => 'de_DE',
            ],
            'HUF' => [
                'decimals' => 0,
                'locale'   => 'hu_HU',
            ],
            'GBP' => [
                'decimals' => 2,
                'locale'   => 'en_UK',
            ],
            'CHF' => [
                'decimals' => 2,
                'locale'   => 'de_CH',
            ],
            'USD' => [
                'decimals' => 2,
                'locale'   => 'en_US',
            ],
        ];
        $this->exchangeRateDate     = strtotime('now');
        foreach (array_keys($this->exchangeRatesDefined) as $value) {
            $this->exchangeRatesValue[$value] = 1;
        }
        $xml = new \XMLReader();
        $x   = EXCHANGE_RATES_LOCAL;
        if ((filemtime(EXCHANGE_RATES_LOCAL) + 90 * 24 * 60 * 60) < time()) {
            $x = EXCHANGE_RATES_SOURCE;
            $f = file_get_contents(EXCHANGE_RATES_SOURCE);
            if ($f !== false) {
                chmod(EXCHANGE_RATES_LOCAL, 0666);
                file_put_contents(EXCHANGE_RATES_LOCAL, $f);
            }
        }
        if ($xml->open($x, 'UTF-8')) {
            while ($xml->read()) {
                if ($xml->nodeType == \XMLReader::ELEMENT) {
                    switch ($xml->localName) {
                        case 'Cube':
                            $v                      = $xml->getAttribute('date');
                            $this->exchangeRateDate = strtotime($v);
                            break;
                        case 'Rate':
                            if (in_array($xml->getAttribute('currency'), array_keys($this->exchangeRatesDefined))) {
                                $c                            = $xml->getAttribute('currency');
                                $this->exchangeRatesValue[$c] = $xml->readInnerXml();
                                if (!is_null($xml->getAttribute('multiplier'))) {
                                    $m                            = $xml->getAttribute('multiplier');
                                    $this->exchangeRatesValue[$c] = $this->exchangeRatesValue[$c] / $m;
                                }
                            }
                            break;
                    }
                }
            }
            $xml->close();
        } else {
            $er = error_get_last();
            echo '<div style="background-color: red; color: white;">'
            . utf8_encode(json_encode($er, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
            . '</div>';
        }
    }

    private function getOvertimes()
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

    private function getValues($longBase)
    {
        $aReturn['cas']      = $this->setHealthFundTax($_REQUEST['ym'], $longBase);
        $aReturn['sanatate'] = $this->setHealthTax($_REQUEST['ym'], $longBase);
        if ($_REQUEST['ym'] < mktime(0, 0, 0, 1, 1, 2008)) {
            $aReturn['somaj'] = $this->setUnemploymentTax($_REQUEST['ym'], $_REQUEST['sn']);
        } else {
            $aReturn['somaj'] = $this->setUnemploymentTax($_REQUEST['ym'], $longBase);
        }
        $wd            = $this->setWorkingDaysInMonth($_REQUEST['ym'], $_REQUEST['pc']);
        $aReturn['ba'] = $this->setFoodTicketsValue($_REQUEST['ym']) * ($wd - $_REQUEST['zfb']);
        $pd            = $this->setPersonalDeduction($_REQUEST['ym'], $longBase, $_REQUEST['pi']);
        $rest          = $longBase - $aReturn['cas'] - $aReturn['sanatate'] - $aReturn['somaj'] - $pd;
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

    private function handleLocalizationSalariu()
    {
        $usedDomain = 'salariu';
        if (isset($_GET['lang'])) {
            $_SESSION['lang'] = filter_var($_GET['lang'], FILTER_SANITIZE_STRING);
        } elseif (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $this->applicationFlags['default_language'];
        }
        /* to avoid potential language injections from other applications that do not applies here */
        if (!in_array($_SESSION['lang'], array_keys($this->applicationFlags['available_languages']))) {
            $_SESSION['lang'] = $this->applicationFlags['default_language'];
        }
        T_setlocale(LC_MESSAGES, $_SESSION['lang']);
        if (function_exists('bindtextdomain')) {
            bindtextdomain($usedDomain, realpath('./locale'));
            bind_textdomain_codeset($usedDomain, 'UTF-8');
            textdomain($usedDomain);
        } else {
            echo 'No gettext extension/library is active in current PHP configuration!';
        }
    }

    /**
     * Tichete de alimente
     * */
    private function setFoodTicketsValue($lngDate)
    {
        $mnth = date('n', $lngDate);
        switch (date('Y', $lngDate)) {
            case 2001:
                if (date('n', $lngDate) <= 2) {
                    $nReturn = 28800;
                } elseif (date('n', $lngDate) <= 8) {
                    $nReturn = 34000;
                } else {
                    $nReturn = 41000;
                }
                break;
            case 2002:
                if (date('n', $lngDate) <= 2) {
                    $nReturn = 41000;
                } elseif (date('n', $lngDate) <= 8) {
                    $nReturn = 45000;
                } else {
                    $nReturn = 50000;
                }
                break;
            case 2003:
                if (date('n', $lngDate) <= 2) {
                    $nReturn = 50000;
                } elseif (date('n', $lngDate) <= 9) {
                    $nReturn = 53000;
                } else {
                    $nReturn = 58000;
                }
                break;
            case 2004:
                if (date('n', $lngDate) <= 2) {
                    $nReturn = 58000;
                } elseif (date('n', $lngDate) <= 8) {
                    $nReturn = 61000;
                } else {
                    $nReturn = 65000;
                }
                break;
            case 2005:
                if (date('n', $lngDate) <= 2) {
                    $nReturn = 65000;
                } elseif (date('n', $lngDate) <= 8) {
                    $nReturn = 68000;
                } else {
                    $nReturn = 70000;
                }
                break;
            case 2006:
                if (date('n', $lngDate) <= 2) {
                    $nReturn = 70000;
                } elseif (date('n', $lngDate) <= 8) {
                    $nReturn = 71500;
                } else {
                    $nReturn = 74100;
                }
                break;
            case 2007:
                if (date('n', $lngDate) <= 78) {
                    $nReturn = 74100;
                } else {
                    $nReturn = 75600;
                }
                break;
            case 2008:
                if (date('n', $lngDate) <= 2) {
                    $nReturn = 75600;
                } elseif (date('n', $lngDate) <= 8) {
                    $nReturn = 78800;
                } else {
                    $nReturn = 83100;
                }
                break;
            case 2009:
                if (date('n', $lngDate) <= 2) {
                    $nReturn = 83100;
                } elseif (date('n', $lngDate) <= 8) {
                    $nReturn = 84800;
                } else {
                    $nReturn = 87200;
                }
                break;
            case 2010:
                $nReturn = 87200;
                break;
            case 2011:
                if (date('n', $lngDate) <= 2) {
                    $nReturn = 87200;
                } else {
                    $nReturn = 90000;
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

    private function setFooterHtml()
    {
        $sReturn   = [];
        $sReturn[] = '<div class="resetOnly author">&copy; 2015 Daniel Popiniuc</div>';
        $sReturn[] = '<hr/>';
        $sReturn[] = '<div class="disclaimer">'
            . _('i18n_Disclaimer')
            . '</div>';
        $sReturn[] = '</body>';
        $sReturn[] = '</html>';
        return implode('', $sReturn);
    }

    private function setFormInput()
    {
        setlocale(LC_TIME, explode('_', $_SESSION['lang'])[0]);
        for ($counter = date('Y'); $counter >= 2001; $counter--) {
            for ($counter2 = 12; $counter2 >= 1; $counter2--) {
                if (($counter == date('Y')) && ($counter2 > date('m'))) {
                    # se limiteaza pana la luna curenta
                } else {
                    $crtDate        = mktime(0, 0, 0, $counter2, 1, $counter);
                    $temp[$crtDate] = strftime('%Y, %m (%B)', $crtDate);
                }
            }
        }
        unset($crtDate);
        $select    = $this->setArray2Select($temp, $_REQUEST['ym'], 'ym', ['size' => 1]);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_CalculationMonth'), $select, 1);
        unset($temp);
        $label     = _('i18n_Form_Label_NegotiatedSalary');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'name'  => 'sn',
                'value' => $_REQUEST['sn'],
                'size'  => 10
            ]) . ' RON', 1);
        $label     = _('i18n_Form_Label_CumulatedAddedValue');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'name'  => 'sc',
                'value' => $_REQUEST['sc'],
                'size'  => 2
            ]) . ' %', 1);
        $label     = _('i18n_Form_Label_AdditionalBruttoAmount');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'name'  => 'pb',
                'value' => $_REQUEST['pb'],
                'size'  => 10
            ]) . ' RON', 1);
        $label     = _('i18n_Form_Label_AdditionalNettoAmount');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'name'  => 'pn',
                'value' => $_REQUEST['pn'],
                'size'  => 10
            ]) . ' RON', 1);
        $label     = sprintf(_('i18n_Form_Label_OvertimeHours'), _('i18n_Form_Label_OvertimeChoice1'), '175%');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'name'  => 'os175',
                'value' => $_REQUEST['os175'],
                'size'  => 2
            ]), 1);
        $label     = sprintf(_('i18n_Form_Label_OvertimeHours'), _('i18n_Form_Label_OvertimeChoice2'), '200%');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'name'  => 'os200',
                'value' => $_REQUEST['os200'],
                'size'  => 2
            ]), 1);
        for ($counter = 0; $counter <= 4; $counter++) {
            $temp[] = $counter;
        }
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_PersonsSupported')
            , $this->setArray2Select($temp, $_REQUEST['pi'], 'pi', ['size' => 1])
            , 1);
        unset($temp);
        $choices   = [
            _('i18n_Form_Label_CatholicEasterFree_ChoiceNo'),
            _('i18n_Form_Label_CatholicEasterFree_ChoiceYes'),
        ];
        $label     = _('i18n_Form_Label_CatholicEasterFree');
        $select    = $this->setArray2Select($choices, $_REQUEST['pc'], 'pc', ['size' => 1]);
        $sReturn[] = $this->setFormRow($label, $select, 1);
        unset($choices);
        $label     = _('i18n_Form_Label_SeisureAmout');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'name'  => 'szamnt',
                'value' => $_REQUEST['szamnt'],
                'size'  => 10
            ]), 1);
        $label     = _('i18n_Form_Label_WorkedDaysWithoutFoodBonuses');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'name'  => 'zfb',
                'value' => $_REQUEST['zfb'],
                'size'  => 2
            ]), 1);
        $label     = _('i18n_Form_Label_FoodBonusesValue');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'name'  => 'gbns',
                'value' => $_REQUEST['gbns'],
                'size'  => 2]), 1);
        $label     = _('i18n_Form_Disclaimer');
        $sReturn[] = $this->setStringIntoTag($this->setStringIntoTag($label . $this->setStringIntoShortTag('input', [
                    'type'  => 'hidden',
                    'name'  => 'action',
                    'value' => $_SERVER['SERVER_NAME']
                ]), 'td', ['colspan' => 2, 'style' => 'color: red;'])
            , 'tr');
        if (isset($_GET['ym'])) {
            $reset_btn      = '';
            $submit_btn_txt = _('i18n_Form_Button_Recalculate');
        } else {
            $reset_btn      = $this->setStringIntoShortTag('input', [
                'type'  => 'reset',
                'id'    => 'reset',
                'value' => _('i18n_Form_Button_Reset'),
                'style' => 'color:#000;'
            ]);
            $submit_btn_txt = _('i18n_Form_Button_Calculate');
        }
        $sReturn[] = $this->setFormRow($reset_btn
            , $this->setStringIntoShortTag('input', [
                'type'  => 'submit',
                'id'    => 'submit',
                'value' => $submit_btn_txt
            ]), 1);
        return $this->setStringIntoTag(
                $this->setStringIntoTag(_('i18n_FieldsetLabel_Inputs'), 'legend')
                . $this->setStringIntoTag(
                    $this->setStringIntoTag(implode('', $sReturn), 'table')
                    , 'form', [
                    'method' => 'get',
                    'action' => $_SERVER['SCRIPT_NAME']
                ])
                , 'fieldset', ['style' => 'float: left;']);
    }

    private function setFormOutput()
    {
        $overtime  = $this->getOvertimes();
        $brut      = ($_REQUEST['sn'] * (1 + $_REQUEST['sc'] / 100) + $_REQUEST['pb'] + $overtime['os175'] + $overtime['os200']) * pow(10, 4);
        $sReturn[] = $this->setFormRow(
            str_replace('%1', date('d.m.Y', $this->exchangeRateDate)
                , _('i18n_Form_Label_ExchangeRateAtDate')), 1000000);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_NegotiatedSalary')
            , $_REQUEST['sn'] * 10000);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_CumulatedAddedValue')
            , $_REQUEST['sn'] * $_REQUEST['sc'] * 100);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_AdditionalBruttoAmount')
            , $_REQUEST['pb'] * 10000);
        $sReturn[] = $this->setFormRow(
            sprintf(_('i18n_Form_Label_OvertimeAmount'), _('i18n_Form_Label_OvertimeChoice1'), '175%')
            , ($overtime['os175'] * pow(10, 4)));
        $sReturn[] = $this->setFormRow(
            sprintf(_('i18n_Form_Label_OvertimeAmount'), _('i18n_Form_Label_OvertimeChoice2'), '200%')
            , ($overtime['os200'] * pow(10, 4)));
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_BruttoSalary')
            , $brut);
        $amount    = $this->getValues($brut);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_PensionFund')
            , $amount['cas']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_HealthTax')
            , $amount['sanatate']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_UnemploymentTax')
            , $amount['somaj']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_ExciseTax')
            , $amount['impozit']);
        $net       = $brut - $amount['cas'] - $amount['somaj'] - $amount['sanatate'] - $amount['impozit'] + $_REQUEST['pn'] * 10000;
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_AdditionalNettoAmount')
            , $_REQUEST['pn'] * 10000);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_NettoSalary'), $net);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_SeisureAmout')
            , $_REQUEST['szamnt'] * 10000);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_NettoSalaryCash')
            , ($net - $_REQUEST['szamnt'] * 10000));
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_WorkingDays')
            , $amount['zile'], 'value');
        $sReturn[] = $this->setFormRow(
            sprintf(_('i18n_Form_Label_FoodBonuses'), _('i18n_Form_Label_FoodBonusesChoiceValue'), _('i18n_Form_Label_FoodBonusesChoiceNo'), ($amount['zile'] - $_REQUEST['zfb'])
            ), $amount['ba']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_FoodBonusesValue'), $amount['gbns']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_Total')
            , ($net + $amount['ba'] + + $amount['gbns'] - $_REQUEST['szamnt'] * 10000));
        return $this->setStringIntoTag(
                $this->setStringIntoTag(
                    sprintf(_('i18n_FieldsetLabel_Results'), strftime('%B', $_GET['ym']), date('Y', $_GET['ym']))
                    , 'legend')
                . $this->setStringIntoTag(implode('', $sReturn)
                    , 'table')
                , 'fieldset', ['style' => 'float: left;']);
    }

    private function setFormRow($text, $value, $type = 'amount')
    {
        $a                = '';
        $defaultCellStyle = ['class' => 'labelS'];
        switch ($text) {
            case _('i18n_Form_Label_NegotiatedSalary'):
            case _('i18n_Form_Label_BruttoSalary'):
            case _('i18n_Form_Label_NettoSalaryCash'):
                $defaultCellStyle = array_merge($defaultCellStyle, [
                    'style' => 'color:#000000;font-weight:bold;'
                ]);
                break;
            case _('i18n_Form_Label_SeisureAmout'):
            case _('i18n_Form_Label_PensionFund'):
            case _('i18n_Form_Label_HealthTax'):
            case _('i18n_Form_Label_UnemploymentTax'):
            case _('i18n_Form_Label_ExciseTax'):
                $defaultCellStyle = array_merge($defaultCellStyle, [
                    'style' => 'color:#ff9900;'
                ]);
                break;
            case _('i18n_Form_Label_Total'):
                $defaultCellStyle = array_merge($defaultCellStyle, [
                    'style' => 'font-weight:bold;color:#009933;font-size:larger;'
                ]);
                break;
        }
        $defaultCellStyle['style'] = '';
        if ((is_numeric($value)) && ($value == 0)) {
            if (isset($defaultCellStyle['style'])) {
                $defaultCellStyle['style'] = 'color:#666;';
            } else {
                $defaultCellStyle = array_merge($defaultCellStyle, [
                    'style' => 'color:#666;'
                ]);
            }
        }
        switch ($type) {
            case 'amount':
                $value                      = $value / pow(10, 4);
                $defaultCellStyle2['style'] = $defaultCellStyle['style']
                    . 'text-align:right;';
                foreach ($this->exchangeRatesDefined as $key2 => $value2) {
                    $fmt         = new \NumberFormatter($value2['locale'], \NumberFormatter::CURRENCY);
                    $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $value2['decimals']);
                    $cellValue[] = $this->setStringIntoTag(
                        $fmt->formatCurrency($value / $this->exchangeRatesValue[$key2], $key2)
                        , 'td', $defaultCellStyle2);
                }
                $value2show = implode('', $cellValue);
                break;
            case 'value':
                $value2show = $this->setStringIntoTag($value . $a
                    , 'td', $defaultCellStyle);
                break;
            default:
                $value2show = $this->setStringIntoTag($value, 'td');
                break;
        }
        if (!in_array($text, ['', '&nbsp;']) && (strpos($text, '<input') === false)) {
            $text .= ':';
        }
        return $this->setStringIntoTag(
                $this->setStringIntoTag($text, 'td', $defaultCellStyle)
                . $value2show, 'tr');
    }

    private function setHeaderHtml()
    {
        return '<!DOCTYPE html>'
            . '<html lang="' . str_replace('_', '-', $_SESSION['lang']) . '">'
            . '<head>'
            . '<meta charset="utf-8" />'
            . '<meta name="viewport" content="width=device-width" />'
            . '<title>' . _('i18n_ApplicationName') . '</title>'
            . $this->setCssFile('css/main.css')
            . '<link rel="icon" href="image/49cecc168d10078dc029d8bf50c7acd8.ico" type="image/x-icon"/>'
            . '</head>'
            . '<body>'
            . '<h1>' . _('i18n_ApplicationName') . '</h1>'
            . $this->setHeaderLanguages()
        ;
    }

    private function setHeaderLanguages()
    {
        $sReturn = [];
        foreach ($this->applicationFlags['available_languages'] as $key => $value) {
            if ($_SESSION['lang'] === $key) {
                $sReturn[] = '<b>' . $value . '</b>';
            } else {
                $sReturn[] = '<a href="?'
                    . (isset($_REQUEST) ? $this->setArray2String4Url('&amp;', $_REQUEST, ['lang']) . '&amp;' : '')
                    . 'lang=' . $key
                    . '">' . $value . '</a>';
            }
        }
        return '<span class="language_box">'
            . implode(' | ', $sReturn)
            . '</span>';
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
    private function setWorkingDaysInMonth($lngDate, $include_easter)
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
