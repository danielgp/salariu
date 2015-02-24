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

class Salariu extends Taxation
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
                'decimals' => 2,
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
        $aReturn['os175'] = ceil($_REQUEST['os175'] * 1.75 * $_REQUEST['sn'] / $m);
        $aReturn['os200'] = ceil($_REQUEST['os200'] * 2 * $_REQUEST['sn'] / $m);
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
        $wd                = $this->setWorkingDaysInMonth($_REQUEST['ym'], $_REQUEST['pc']);
        $aReturn['ba']     = $this->setFoodTicketsValue($_REQUEST['ym']) * ($wd - $_REQUEST['zfb']);
        $aReturn['pd']     = $this->setPersonalDeduction($_REQUEST['ym'], ($longBase + $aReturn['ba']), $_REQUEST['pi']);
        $restArrayToDeduct = [
            $aReturn['cas'],
            $aReturn['sanatate'],
            $aReturn['somaj'],
            $aReturn['pd'],
        ];
        $rest              = $longBase - array_sum($restArrayToDeduct);
        if ($_REQUEST['ym'] >= mktime(0, 0, 0, 7, 1, 2010)) {
            $rest += round($aReturn['ba'], -4);
        }
        if ($_REQUEST['ym'] >= mktime(0, 0, 0, 10, 1, 2010)) {
            $aReturn['gbns'] = $_REQUEST['gbns'] * pow(10, 4);
            $rest += round($aReturn['gbns'], -4);
        }
        //$rest += $_REQUEST['afet'] * pow(10, 4);
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

    private function setFooterHtml()
    {
        $sReturn   = [];
        $sReturn[] = '<div class="resetOnly author">&copy; 2015 Daniel Popiniuc</div>';
        $sReturn[] = '<hr/>';
        $sReturn[] = '<div class="disclaimer">'
            . _('i18n_Disclaimer')
            . '</div>';
        return $this->setFooterCommon(implode('', $sReturn));
    }

    private function setFormInput()
    {
        setlocale(LC_TIME, explode('_', $_SESSION['lang'])[0]);

        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_CalculationMonth'), $this->setFormInputSelect(), 1);
        $label     = _('i18n_Form_Label_NegotiatedSalary');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'sn',
                'value' => $_REQUEST['sn'],
                'size'  => 10
            ]) . ' RON', 1);
        $label     = _('i18n_Form_Label_CumulatedAddedValue');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'sc',
                'value' => $_REQUEST['sc'],
                'size'  => 2
            ]) . ' %', 1);
        $label     = _('i18n_Form_Label_AdditionalBruttoAmount');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'pb',
                'value' => $_REQUEST['pb'],
                'size'  => 10
            ]) . ' RON', 1);
        $label     = _('i18n_Form_Label_AdditionalNettoAmount');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'pn',
                'value' => $_REQUEST['pn'],
                'size'  => 10
            ]) . ' RON', 1);
        $label     = sprintf(_('i18n_Form_Label_OvertimeHours'), _('i18n_Form_Label_OvertimeChoice1'), '175%');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'os175',
                'value' => $_REQUEST['os175'],
                'size'  => 2
            ]), 1);
        $label     = sprintf(_('i18n_Form_Label_OvertimeHours'), _('i18n_Form_Label_OvertimeChoice2'), '200%');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'os200',
                'value' => $_REQUEST['os200'],
                'size'  => 2
            ]), 1);
        for ($counter = 0; $counter <= 4; $counter++) {
            $temp2[] = $counter;
        }
        $selectTemp = $this->setArray2Select($temp2, $_REQUEST['pi'], 'pi', ['size' => 1]);
        $sReturn[]  = $this->setFormRow(_('i18n_Form_Label_PersonsSupported'), $selectTemp, 1);
        $choices    = [
            _('i18n_Form_Label_CatholicEasterFree_ChoiceNo'),
            _('i18n_Form_Label_CatholicEasterFree_ChoiceYes'),
        ];
        $label      = _('i18n_Form_Label_CatholicEasterFree');
        $select     = $this->setArray2Select($choices, $_REQUEST['pc'], 'pc', ['size' => 1]);
        $sReturn[]  = $this->setFormRow($label, $select, 1);
        $label      = _('i18n_Form_Label_SeisureAmout');
        $sReturn[]  = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'szamnt',
                'value' => $_REQUEST['szamnt'],
                'size'  => 10
            ]), 1);
        $label      = _('i18n_Form_Label_WorkedDaysWithoutFoodBonuses');
        $sReturn[]  = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'zfb',
                'value' => $_REQUEST['zfb'],
                'size'  => 2
            ]), 1);
        $label      = _('i18n_Form_Label_FoodBonusesValue');
        $sReturn[]  = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'gbns',
                'value' => $_REQUEST['gbns'],
                'size'  => 2]), 1);
        $label      = _('i18n_Form_Label_AdvantagesForExciseTax');
        $sReturn[]  = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'afet',
                'value' => $_REQUEST['afet'],
                'size'  => 2]), 1);
        $label      = _('i18n_Form_Disclaimer');
        $sReturn[]  = $this->setStringIntoTag($this->setStringIntoTag($label . $this->setStringIntoShortTag('input', [
                    'type'  => 'hidden',
                    'name'  => 'action',
                    'value' => $_SERVER['SERVER_NAME']
                ]), 'td', ['colspan' => 2, 'style' => 'color: red;']), 'tr');
        if (isset($_REQUEST['ym'])) {
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
        $sReturn[] = $this->setFormRow($reset_btn, $this->setStringIntoShortTag('input', [
                'type'  => 'submit',
                'id'    => 'submit',
                'value' => $submit_btn_txt
            ]), 1);
        $frm       = $this->setStringIntoTag($this->setStringIntoTag(implode('', $sReturn), 'table'), 'form', [
            'method' => 'get',
            'action' => $_SERVER['SCRIPT_NAME']
        ]);
        return $this->setStringIntoTag(implode('', [
                $this->setStringIntoTag(_('i18n_FieldsetLabel_Inputs'), 'legend'),
                $frm
                ]), 'fieldset', ['style' => 'float: left;']);
    }

    private function setFormInputSelect()
    {
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
        return $this->setArray2Select($temp, $_REQUEST['ym'], 'ym', ['size' => 1]);
    }

    private function setFormOutput()
    {
        $overtime  = $this->getOvertimes();
        $additions = $_REQUEST['pb'] + $overtime['os175'] + $overtime['os200'];
        $brut      = ($_REQUEST['sn'] * (1 + $_REQUEST['sc'] / 100) + $additions) * pow(10, 4);
        $xRate     = str_replace('%1', date('d.m.Y', $this->exchangeRateDate), _('i18n_Form_Label_ExchangeRateAtDate'));
        $sReturn[] = $this->setFormRow($xRate, 1000000);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_NegotiatedSalary'), $_REQUEST['sn'] * 10000);
        $prima     = $_REQUEST['sn'] * $_REQUEST['sc'] * 100;
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_CumulatedAddedValue'), $prima);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_AdditionalBruttoAmount'), $_REQUEST['pb'] * 10000);
        $ovTime    = [
            'main' => _('i18n_Form_Label_OvertimeAmount'),
            1      => _('i18n_Form_Label_OvertimeChoice1'),
            2      => _('i18n_Form_Label_OvertimeChoice2'),
        ];
        $sReturn[] = $this->setFormRow(sprintf($ovTime['main'], $ovTime[1], '175%'), ($overtime['os175'] * pow(10, 4)));
        $sReturn[] = $this->setFormRow(sprintf($ovTime['main'], $ovTime[2], '200%'), ($overtime['os200'] * pow(10, 4)));
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_BruttoSalary'), $brut);
        //$brut += $_REQUEST['afet'] * pow(10, 4);
        $amount    = $this->getValues($brut);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_PensionFund'), $amount['cas']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_UnemploymentTax'), $amount['somaj']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_HealthTax'), $amount['sanatate']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_PersonalDeduction'), $amount['pd']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_ExciseTax'), $amount['impozit']);
        $retineri  = $amount['cas'] + $amount['somaj'] + $amount['sanatate'] + $amount['impozit'];
        $net       = $brut - $retineri + $_REQUEST['pn'] * 10000;
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_AdditionalNettoAmount'), $_REQUEST['pn'] * 10000);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_NettoSalary'), $net);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_SeisureAmout'), $_REQUEST['szamnt'] * 10000);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_NettoSalaryCash'), ($net - $_REQUEST['szamnt'] * 10000));
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_WorkingDays'), $amount['zile'], 'value');
        $fBonus    = [
            'main'  => _('i18n_Form_Label_FoodBonuses'),
            'no'    => _('i18n_Form_Label_FoodBonusesChoiceNo'),
            'value' => _('i18n_Form_Label_FoodBonusesChoiceValue')
        ];
        $fBonusTxt = sprintf($fBonus['main'], $fBonus['value'], $fBonus['no'], ($amount['zile'] - $_REQUEST['zfb']));
        $sReturn[] = $this->setFormRow($fBonusTxt, $amount['ba']);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_FoodBonusesValue'), $amount['gbns']);
        $total     = ($net + $amount['ba'] + $amount['gbns'] - $_REQUEST['szamnt'] * 10000);
        $sReturn[] = $this->setFormRow(_('i18n_Form_Label_Total'), $total);
        $crtMonth  = strftime('%B', $_REQUEST['ym']);
        $legend    = sprintf(_('i18n_FieldsetLabel_Results'), $crtMonth, date('Y', $_REQUEST['ym']));
        return $this->setStringIntoTag(implode('', [
                $this->setStringIntoTag($legend, 'legend'),
                $this->setStringIntoTag(implode('', $sReturn), 'table')
                ]), 'fieldset', ['style' => 'float: left;']);
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
                    $finalValue  = $fmt->formatCurrency($value / $this->exchangeRatesValue[$key2], $key2);
                    $cellValue[] = $this->setStringIntoTag($finalValue, 'td', $defaultCellStyle2);
                }
                $value2show        = implode('', $cellValue);
                break;
            case 'value':
                $defaultCellStyle2 = array_merge($defaultCellStyle, [
                    'colspan' => count($this->exchangeRatesDefined)
                ]);
                $value2show        = $this->setStringIntoTag($value . $a, 'td', $defaultCellStyle2);
                break;
            default:
                $value2show        = $this->setStringIntoTag($value, 'td');
                break;
        }
        if (!in_array($text, ['', '&nbsp;']) && (strpos($text, '<input') === false)) {
            $text .= ':';
        }
        return $this->setStringIntoTag($this->setStringIntoTag($text, 'td', $defaultCellStyle) . $value2show, 'tr');
    }

    private function setHeaderHtml()
    {
        return $this->setHeaderCommon([
                'lang'  => str_replace('_', '-', $_SESSION['lang']),
                'title' => _('i18n_ApplicationName'),
                'css'   => 'css/main.css',
            ])
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
}
