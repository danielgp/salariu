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

    use \danielgp\common_lib\CommonCode,
        \danielgp\salariu\Bonuses,
        \danielgp\salariu\Taxation;

    private $appFlags;
    private $tApp = null;

    public function __construct()
    {
        $this->appFlags = [
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
        $this->appFlags['currency_exchanges']          = [
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
        $this->appFlags['currency_exchange_rate_date'] = strtotime('now');
        $k                                             = array_keys($this->appFlags['currency_exchanges']);
        foreach ($k as $value) {
            $this->appFlags['currency_exchange_rate_value'][$value] = 1;
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
                            $this->appFlags['currency_exchange_rate_date'] = strtotime($xml->getAttribute('date'));
                            break;
                        case 'Rate':
                            if (in_array($xml->getAttribute('currency'), $k)) {
                                $c  = $xml->getAttribute('currency');
                                $vl = $xml->readInnerXml();
                                if (!is_null($xml->getAttribute('multiplier'))) {
                                    $vl = $vl / $xml->getAttribute('multiplier');
                                }
                                $this->appFlags['currency_exchange_rate_value'][$c] = $vl;
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

    private function getValues($lngBase)
    {
        $aReturn['cas']      = $this->setHealthFundTax($_REQUEST['ym'], $lngBase);
        $aReturn['sanatate'] = $this->setHealthTax($_REQUEST['ym'], $lngBase);
        if ($_REQUEST['ym'] < mktime(0, 0, 0, 1, 1, 2008)) {
            $aReturn['somaj'] = $this->setUnemploymentTax($_REQUEST['ym'], $_REQUEST['sn']);
        } else {
            $aReturn['somaj'] = $this->setUnemploymentTax($_REQUEST['ym'], $lngBase);
        }
        $wd                = $this->setWorkingDaysInMonth($_REQUEST['ym'], $_REQUEST['pc']);
        $aReturn['ba']     = $this->setFoodTicketsValue($_REQUEST['ym']) * ($wd - $_REQUEST['zfb']);
        $aReturn['pd']     = $this->setPersonalDeduction($_REQUEST['ym'], ($lngBase + $aReturn['ba']), $_REQUEST['pi']);
        $restArrayToDeduct = [
            $aReturn['cas'],
            $aReturn['sanatate'],
            $aReturn['somaj'],
            $aReturn['pd'],
        ];
        $rest              = $lngBase - array_sum($restArrayToDeduct);
        if ($_REQUEST['ym'] >= mktime(0, 0, 0, 7, 1, 2010)) {
            $rest += round($aReturn['ba'], -4);
        }
        if ($_REQUEST['ym'] >= mktime(0, 0, 0, 10, 1, 2010)) {
            $aReturn['gbns'] = $_REQUEST['gbns'] * pow(10, 4);
            $rest += round($aReturn['gbns'], -4);
        }
        $rest += $_REQUEST['afet'] * pow(10, 4);
        $aReturn['impozit'] = $this->setIncomeTax($_REQUEST['ym'], $rest);
        $aReturn['zile']    = $this->setWorkingDaysInMonth($_REQUEST['ym'], $_REQUEST['pc']);
        return $aReturn;
    }

    private function handleLocalizationSalariu()
    {
        if (isset($_GET['lang'])) {
            $_SESSION['lang'] = filter_var($_GET['lang'], FILTER_SANITIZE_STRING);
        } elseif (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $this->appFlags['default_language'];
        }
        /* to avoid potential language injections from other applications that do not applies here */
        if (!in_array($_SESSION['lang'], array_keys($this->appFlags['available_languages']))) {
            $_SESSION['lang'] = $this->appFlags['default_language'];
        }
        $localizationFile = 'locale/' . $_SESSION['lang'] . '/LC_MESSAGES/salariu.mo';
        $translations     = \Gettext\Extractors\Mo::fromFile($localizationFile);
        $this->tApp       = new \Gettext\Translator();
        $this->tApp->loadTranslations($translations);
    }

    private function setFooterHtml()
    {
        $sReturn   = [];
        $sReturn[] = '<div class="resetOnly author">&copy; 2015 Daniel Popiniuc</div>';
        $sReturn[] = '<hr/>';
        $sReturn[] = '<div class="disclaimer">'
            . $this->tApp->gettext('i18n_Disclaimer')
            . '</div>';
        return $this->setFooterCommon(implode('', $sReturn));
    }

    private function setFormInput()
    {
        $label     = $this->tApp->gettext('i18n_Form_Label_CalculationMonth');
        $sReturn[] = $this->setFormRow($label, $this->setFormInputSelect(), 1);
        $label     = $this->tApp->gettext('i18n_Form_Label_NegotiatedSalary');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'sn',
                'value' => $_REQUEST['sn'],
                'size'  => 10
            ]) . ' RON', 1);
        $label     = $this->tApp->gettext('i18n_Form_Label_CumulatedAddedValue');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'sc',
                'value' => $_REQUEST['sc'],
                'size'  => 2
            ]) . ' %', 1);
        $label     = $this->tApp->gettext('i18n_Form_Label_AdditionalBruttoAmount');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'pb',
                'value' => $_REQUEST['pb'],
                'size'  => 10
            ]) . ' RON', 1);
        $label     = $this->tApp->gettext('i18n_Form_Label_AdditionalNettoAmount');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'pn',
                'value' => $_REQUEST['pn'],
                'size'  => 10
            ]) . ' RON', 1);
        $pieces    = [
            $this->tApp->gettext('i18n_Form_Label_OvertimeHours'),
            $this->tApp->gettext('i18n_Form_Label_OvertimeChoice1'),
        ];
        $label     = sprintf($pieces[0], $pieces[1], '175%');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'os175',
                'value' => $_REQUEST['os175'],
                'size'  => 2
            ]), 1);
        $pieces    = [
            $this->tApp->gettext('i18n_Form_Label_OvertimeHours'),
            $this->tApp->gettext('i18n_Form_Label_OvertimeChoice2'),
        ];
        $label     = sprintf($pieces[0], $pieces[1], '200%');
        $sReturn[] = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'os200',
                'value' => $_REQUEST['os200'],
                'size'  => 2
            ]), 1);
        for ($counter = 0; $counter <= 4; $counter++) {
            $temp2[] = $counter;
        }
        $selectTemp = $this->setArrayToSelect($temp2, $_REQUEST['pi'], 'pi', ['size' => 1]);
        $sReturn[]  = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_PersonsSupported'), $selectTemp, 1);
        $choices    = [
            $this->tApp->gettext('i18n_Form_Label_CatholicEasterFree_ChoiceNo'),
            $this->tApp->gettext('i18n_Form_Label_CatholicEasterFree_ChoiceYes'),
        ];
        $label      = $this->tApp->gettext('i18n_Form_Label_CatholicEasterFree');
        $select     = $this->setArrayToSelect($choices, $_REQUEST['pc'], 'pc', ['size' => 1]);
        $sReturn[]  = $this->setFormRow($label, $select, 1);
        $label      = $this->tApp->gettext('i18n_Form_Label_SeisureAmout');
        $sReturn[]  = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'szamnt',
                'value' => $_REQUEST['szamnt'],
                'size'  => 10
            ]), 1);
        $label      = $this->tApp->gettext('i18n_Form_Label_WorkedDaysWithoutFoodBonuses');
        $sReturn[]  = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'zfb',
                'value' => $_REQUEST['zfb'],
                'size'  => 2
            ]), 1);
        $label      = $this->tApp->gettext('i18n_Form_Label_FoodBonusesValue');
        $sReturn[]  = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'gbns',
                'value' => $_REQUEST['gbns'],
                'size'  => 2]), 1);
        $label      = $this->tApp->gettext('i18n_Form_Label_AdvantagesForExciseTax');
        $sReturn[]  = $this->setFormRow($label, $this->setStringIntoShortTag('input', [
                'type'  => 'text',
                'name'  => 'afet',
                'value' => $_REQUEST['afet'],
                'size'  => 2]), 1);
        $label      = $this->tApp->gettext('i18n_Form_Disclaimer');
        $sReturn[]  = $this->setStringIntoTag($this->setStringIntoTag($label . $this->setStringIntoShortTag('input', [
                    'type'  => 'hidden',
                    'name'  => 'action',
                    'value' => $_SERVER['SERVER_NAME']
                ]), 'td', ['colspan' => 2, 'style' => 'color: red;']), 'tr');
        if (isset($_REQUEST['ym'])) {
            $reset_btn      = '';
            $submit_btn_txt = $this->tApp->gettext('i18n_Form_Button_Recalculate');
        } else {
            $reset_btn      = $this->setStringIntoShortTag('input', [
                'type'  => 'reset',
                'id'    => 'reset',
                'value' => $this->tApp->gettext('i18n_Form_Button_Reset'),
                'style' => 'color:#000;'
            ]);
            $submit_btn_txt = $this->tApp->gettext('i18n_Form_Button_Calculate');
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
                $this->setStringIntoTag($this->tApp->gettext('i18n_FieldsetLabel_Inputs'), 'legend'),
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
        return $this->setArrayToSelect($temp, $_REQUEST['ym'], 'ym', ['size' => 1]);
    }

    private function setFormOutput()
    {
        $overtime  = $this->getOvertimes();
        $additions = $_REQUEST['pb'] + $overtime['os175'] + $overtime['os200'];
        $brut      = ($_REQUEST['sn'] * (1 + $_REQUEST['sc'] / 100) + $additions) * pow(10, 4);
        $text      = $this->tApp->gettext('i18n_Form_Label_ExchangeRateAtDate');
        $xRate     = str_replace('%1', date('d.m.Y', $this->appFlags['currency_exchange_rate_date']), $text);
        $sReturn[] = $this->setFormRow($xRate, 1000000);
        $text      = $this->tApp->gettext('i18n_Form_Label_NegotiatedSalary');
        $sReturn[] = $this->setFormRow($text, $_REQUEST['sn'] * 10000);
        $prima     = $_REQUEST['sn'] * $_REQUEST['sc'] * 100;
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_CumulatedAddedValue'), $prima);
        $text      = $this->tApp->gettext('i18n_Form_Label_AdditionalBruttoAmount');
        $sReturn[] = $this->setFormRow($text, $_REQUEST['pb'] * 10000);
        $ovTime    = [
            'main' => $this->tApp->gettext('i18n_Form_Label_OvertimeAmount'),
            1      => $this->tApp->gettext('i18n_Form_Label_OvertimeChoice1'),
            2      => $this->tApp->gettext('i18n_Form_Label_OvertimeChoice2'),
        ];
        $sReturn[] = $this->setFormRow(sprintf($ovTime['main'], $ovTime[1], '175%'), ($overtime['os175'] * pow(10, 4)));
        $sReturn[] = $this->setFormRow(sprintf($ovTime['main'], $ovTime[2], '200%'), ($overtime['os200'] * pow(10, 4)));
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_BruttoSalary'), $brut);
        $brut += $_REQUEST['afet'] * pow(10, 4);
        $amount    = $this->getValues($brut);
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_PensionFund'), $amount['cas']);
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_UnemploymentTax'), $amount['somaj']);
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_HealthTax'), $amount['sanatate']);
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_PersonalDeduction'), $amount['pd']);
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_ExciseTax'), $amount['impozit']);
        $retineri  = $amount['cas'] + $amount['somaj'] + $amount['sanatate'] + $amount['impozit'];
        $net       = $brut - $retineri + $_REQUEST['pn'] * 10000;
        $text      = $this->tApp->gettext('i18n_Form_Label_AdditionalNettoAmount');
        $sReturn[] = $this->setFormRow($text, $_REQUEST['pn'] * 10000);
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_NettoSalary'), $net);
        $text      = $this->tApp->gettext('i18n_Form_Label_SeisureAmout');
        $sReturn[] = $this->setFormRow($text, $_REQUEST['szamnt'] * 10000);
        $text      = $this->tApp->gettext('i18n_Form_Label_NettoSalaryCash');
        $sReturn[] = $this->setFormRow($text, ($net - $_REQUEST['szamnt'] * 10000));
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_WorkingDays'), $amount['zile'], 'value');
        $fBonus    = [
            'main'  => $this->tApp->gettext('i18n_Form_Label_FoodBonuses'),
            'no'    => $this->tApp->gettext('i18n_Form_Label_FoodBonusesChoiceNo'),
            'value' => $this->tApp->gettext('i18n_Form_Label_FoodBonusesChoiceValue')
        ];
        $fBonusTxt = sprintf($fBonus['main'], $fBonus['value'], $fBonus['no'], ($amount['zile'] - $_REQUEST['zfb']));
        $sReturn[] = $this->setFormRow($fBonusTxt, $amount['ba']);
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_FoodBonusesValue'), $amount['gbns']);
        $total     = ($net + $amount['ba'] + $amount['gbns'] - $_REQUEST['szamnt'] * 10000);
        $sReturn[] = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_Total'), $total);
        setlocale(LC_TIME, explode('_', $_SESSION['lang'])[0]);
        $crtMonth  = strftime('%B', $_REQUEST['ym']);
        $legend    = sprintf($this->tApp->gettext('i18n_FieldsetLabel_Results'), $crtMonth, date('Y', $_REQUEST['ym']));
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
            case $this->tApp->gettext('i18n_Form_Label_NegotiatedSalary'):
            case $this->tApp->gettext('i18n_Form_Label_BruttoSalary'):
            case $this->tApp->gettext('i18n_Form_Label_NettoSalaryCash'):
                $defaultCellStyle = array_merge($defaultCellStyle, [
                    'style' => 'color:#000000;font-weight:bold;'
                ]);
                break;
            case $this->tApp->gettext('i18n_Form_Label_SeisureAmout'):
            case $this->tApp->gettext('i18n_Form_Label_PensionFund'):
            case $this->tApp->gettext('i18n_Form_Label_HealthTax'):
            case $this->tApp->gettext('i18n_Form_Label_UnemploymentTax'):
            case $this->tApp->gettext('i18n_Form_Label_ExciseTax'):
                $defaultCellStyle = array_merge($defaultCellStyle, [
                    'style' => 'color:#ff9900;'
                ]);
                break;
            case $this->tApp->gettext('i18n_Form_Label_Total'):
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
                $defaultCellStyle2['style'] = $defaultCellStyle['style'] . 'text-align:right;';
                foreach ($this->appFlags['currency_exchanges'] as $key2 => $value2) {
                    $fmt         = new \NumberFormatter($value2['locale'], \NumberFormatter::CURRENCY);
                    $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $value2['decimals']);
                    $x           = $this->appFlags['currency_exchange_rate_value'][$key2];
                    $finalValue  = $fmt->formatCurrency($value / $x, $key2);
                    $cellValue[] = $this->setStringIntoTag($finalValue, 'td', $defaultCellStyle2);
                }
                $value2show        = implode('', $cellValue);
                break;
            case 'value':
                $defaultCellStyle2 = array_merge($defaultCellStyle, [
                    'colspan' => count($this->appFlags['currency_exchanges'])
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
                'title' => $this->tApp->gettext('i18n_ApplicationName'),
                'css'   => 'css/main.css',
            ])
            . '<h1>' . $this->tApp->gettext('i18n_ApplicationName') . '</h1>'
            . $this->setHeaderLanguages()
        ;
    }

    private function setHeaderLanguages()
    {
        $sReturn = [];
        foreach ($this->appFlags['available_languages'] as $key => $value) {
            if ($_SESSION['lang'] === $key) {
                $sReturn[] = '<b>' . $value . '</b>';
            } else {
                $sReturn[] = '<a href="?'
                    . (isset($_REQUEST) ? $this->setArrayToStringForUrl('&amp;', $_REQUEST, ['lang']) . '&amp;' : '')
                    . 'lang=' . $key
                    . '">' . $value . '</a>';
            }
        }
        return '<span class="language_box">'
            . implode(' | ', $sReturn)
            . '</span>';
    }
}
