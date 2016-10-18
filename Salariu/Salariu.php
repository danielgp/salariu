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

class Salariu
{

    use \danielgp\bank_holidays\Romanian,
        \danielgp\common_lib\CommonCode,
        \danielgp\salariu\Bonuses,
        \danielgp\salariu\Taxation;

    private $appFlags;
    private $tApp = null;

    public function __construct()
    {
        $configPath        = 'Salariu' . DIRECTORY_SEPARATOR . 'config';
        $interfaceElements = $this->readTypeFromJsonFileUniversal($configPath, 'interfaceElements');
        $this->appFlags    = [
            'FI'   => $interfaceElements['Form Input'],
            'TCAS' => $interfaceElements['Table Cell Applied Style'],
            'TCSD' => $interfaceElements['Table Cell Style Definitions'],
        ];
        $this->initializeSprGlbAndSession();
        $this->handleLocalizationSalariu($interfaceElements['Application']);
        echo $this->setHeaderHtml();
        echo $this->setFormInput();
        $this->refreshExchangeRatesFile($interfaceElements['Application']);
        $this->setCurrencyExchangeVariables($interfaceElements['Relevant Currencies']);
        $this->getExchangeRates($interfaceElements['Application'], $interfaceElements['Relevant Currencies']);
        $aryStngs          = $this->readTypeFromJsonFileUniversal($configPath, 'valuesToCompute');
        echo $this->setFormOutput($aryStngs);
        echo $this->setFooterHtml($interfaceElements['Application']);
    }

    private function buildArrayOfFieldsStyled()
    {
        $sReturn = [];
        foreach ($this->appFlags['TCAS'] as $key => $value) {
            $sReturn[$this->tApp->gettext($key)] = $value;
        }
        return $sReturn;
    }

    private function buildStyleForCellFormat($styleId)
    {
        $sReturn = [];
        foreach ($this->appFlags['TCSD'][$styleId] as $key => $value) {
            $sReturn[] = $key . ':' . $value;
        }
        return implode(';', $sReturn) . ';';
    }

    private function getExchangeRates($appSettings, $aryRelevantCurrencies)
    {
        $xml = new \XMLReader();
        if ($xml->open($appSettings['Exchange Rate Local'], 'UTF-8')) {
            while ($xml->read()) {
                if ($xml->nodeType == \XMLReader::ELEMENT) {
                    switch ($xml->localName) {
                        case 'Cube':
                            $this->appFlags['currency_exchange_rate_date'] = strtotime($xml->getAttribute('date'));
                            break;
                        case 'Rate':
                            if (array_key_exists($xml->getAttribute('currency'), $aryRelevantCurrencies)) {
                                $cVal = $xml->readInnerXml();
                                if (!is_null($xml->getAttribute('multiplier'))) {
                                    $cVal = $cVal / $xml->getAttribute('multiplier');
                                }
                                $this->appFlags['currency_exchange_rate_value'][$xml->getAttribute('currency')] = $cVal;
                            }
                            break;
                    }
                }
            }
            $xml->close();
        }
    }

    private function getOvertimes($aryStngs)
    {
        $pcToBoolean = [0 => true, 1 => false];
        $pcBoolean   = $pcToBoolean[$this->tCmnSuperGlobals->get('pc')];
        $ymVal       = $this->tCmnSuperGlobals->get('ym');
        $snVal       = $this->tCmnSuperGlobals->get('sn');
        $mnth        = $this->setMonthlyAverageWorkingHours($ymVal, $aryStngs, $pcBoolean);
        return [
            'os175' => ceil($this->tCmnSuperGlobals->get('os175') * 1.75 * $snVal / $mnth),
            'os200' => ceil($this->tCmnSuperGlobals->get('os200') * 2 * $snVal / $mnth),
        ];
    }

    private function getValues($lngBase, $aStngs)
    {
        $inDate           = $this->tCmnSuperGlobals->get('ym');
        $inDT             = new \DateTime(date('Y/m/d', $inDate));
        $wkDay            = $this->setWorkingDaysInMonth($inDT, $_REQUEST['pc']);
        $nMealDays        = ($wkDay - $_REQUEST['zfb']);
        $shLbl            = [
            'HFP'  => 'Health Fund Percentage',
            'HFUL' => 'Health Fund Upper Limit',
            'HTP'  => 'Health Tax Percentage',
            'IT'   => 'Income Tax',
            'MTV'  => 'Meal Ticket Value',
        ];
        $unemploymentBase = $lngBase;
        if ($this->tCmnSuperGlobals->get('ym') < mktime(0, 0, 0, 1, 1, 2008)) {
            $unemploymentBase = $_REQUEST['sn'];
        }
        $aReturn           = [
            'ba'       => $this->setFoodTicketsValue($inDate, $aStngs[$shLbl['MTV']]) * $nMealDays,
            'cas'      => $this->setHealthFundTax($inDate, $lngBase, $aStngs[$shLbl['HFP']], $aStngs[$shLbl['HFUL']]),
            'sanatate' => $this->setHealthTax($inDate, $lngBase, $aStngs[$shLbl['HTP']]),
            'somaj'    => $this->setUnemploymentTax($inDate, $unemploymentBase),
        ];
        $pdVal             = [
            $inDate,
            ($lngBase + $aReturn['ba']),
            $_REQUEST['pi'],
            $aStngs['Personal Deduction'],
        ];
        $aReturn['pd']     = $this->setPersonalDeduction($pdVal[0], $pdVal[1], $pdVal[2], $pdVal[3]);
        $restArrayToDeduct = [
            $aReturn['cas'],
            $aReturn['sanatate'],
            $aReturn['somaj'],
            $aReturn['pd'],
        ];
        $rest              = $lngBase - array_sum($restArrayToDeduct);
        if ($inDate >= mktime(0, 0, 0, 7, 1, 2010)) {
            $rest += round($aReturn['ba'], -4);
            if ($inDate >= mktime(0, 0, 0, 10, 1, 2010)) {
                $aReturn['gbns'] = $_REQUEST['gbns'] * pow(10, 4);
                $rest            += round($aReturn['gbns'], -4);
            }
        }
        $rest               += $_REQUEST['afet'] * pow(10, 4);
        $aReturn['impozit'] = $this->setIncomeTax($inDate, $rest, $aStngs[$shLbl['IT']]);
        $aReturn['zile']    = $wkDay;
        return $aReturn;
    }

    private function handleLocalizationSalariu($appSettings)
    {
        if (is_null($this->tCmnSuperGlobals->get('lang')) && is_null($this->tCmnSession->get('lang'))) {
            $this->tCmnSession->set('lang', $appSettings['Default Language']);
        } elseif (!is_null($this->tCmnSuperGlobals->get('lang'))) {
            $this->tCmnSession->set('lang', filter_var($this->tCmnSuperGlobals->get('lang'), FILTER_SANITIZE_STRING));
        }
        /* to avoid potential language injections from other applications that do not applies here */
        if (!array_key_exists($this->tCmnSession->get('lang'), $appSettings['Available Languages'])) {
            $this->tCmnSession->set('lang', $appSettings['Default Language']);
        }
        $localizationFile = 'Salariu/locale/' . $this->tCmnSession->get('lang') . '/LC_MESSAGES/salariu.mo';
        $translations     = new \Gettext\Translations;
        $translations->addFromMoFile($localizationFile);
        $this->tApp       = new \Gettext\Translator();
        $this->tApp->loadTranslations($translations);
    }

    private function refreshExchangeRatesFile($appSettings)
    {
        if ((filemtime($appSettings['Exchange Rate Local']) + 90 * 24 * 60 * 60) < time()) {
            $fCntnt = file_get_contents($appSettings['Exchange Rate Source']);
            if ($fCntnt !== false) {
                file_put_contents($appSettings['Exchange Rate Local'], $fCntnt);
                chmod($appSettings['Exchange Rate Local'], 0666);
            }
        }
    }

    private function setCurrencyExchangeVariables($aryRelevantCurrencies)
    {
        $this->appFlags['currency_exchanges']          = $aryRelevantCurrencies;
        $this->appFlags['currency_exchange_rate_date'] = strtotime('now');
        $krncy                                         = array_keys($this->appFlags['currency_exchanges']);
        foreach ($krncy as $value) {
            $this->appFlags['currency_exchange_rate_value'][$value] = 1;
        }
    }

    private function setFooterHtml($appSettings)
    {
        $sReturn = $this->setUpperRightBoxLanguages($appSettings['Available Languages'])
                . '<div class="resetOnly author">&copy; ' . date('Y') . ' '
                . $appSettings['Copyright Holder'] . '</div>'
                . '<hr/>'
                . '<div class="disclaimer">'
                . $this->tApp->gettext('i18n_Disclaimer')
                . '</div>';
        return $this->setFooterCommon($sReturn);
    }

    private function setFormInput()
    {
        $sReturn      = [];
        $sReturn[]    = $this->setFormRow($this->setLabel('ym'), $this->setFormInputSelectYM(), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('sn'), $this->setFormInputText('sn', 10, 'RON'), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('sc'), $this->setFormInputText('sc', 2, '%'), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('pb'), $this->setFormInputText('pb', 10, 'RON'), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('pn'), $this->setFormInputText('pn', 10, 'RON'), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('os175'), $this->setFormInputText('os175', 2, ''), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('os200'), $this->setFormInputText('os200', 2, ''), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('pi'), $this->setFormInputSelectPI(), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('pc'), $this->setFormInputSelectPC(), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('szamnt'), $this->setFormInputText('szamnt', 2, ''), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('zfb'), $this->setFormInputText('zfb', 2, ''), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('gbns'), $this->setFormInputText('gbns', 2, ''), 1);
        $sReturn[]    = $this->setFormRow($this->setLabel('afet'), $this->setFormInputText('afet', 2, ''), 1);
        $label        = $this->tApp->gettext('i18n_Form_Disclaimer');
        $hiddenField  = $this->setStringIntoShortTag('input', [
            'type'  => 'hidden',
            'name'  => 'action',
            'value' => $this->tCmnSuperGlobals->server->get['SERVER_NAME'],
        ]);
        $sReturn[]    = $this->setStringIntoTag($this->setStringIntoTag($label . $hiddenField, 'td', [
                    'colspan' => 2,
                    'style'   => 'color: red;'
                ]), 'tr');
        $submitBtnTxt = $this->tApp->gettext('i18n_Form_Button_Recalculate');
        $sReturn[]    = $this->setFormRow('', $this->setStringIntoShortTag('input', [
                    'type'  => 'submit',
                    'id'    => 'submit',
                    'value' => $submitBtnTxt
                ]), 1);
        $frm          = $this->setStringIntoTag($this->setStringIntoTag(implode('', $sReturn), 'table'), 'form', [
            'method' => 'get',
            'action' => $this->tCmnSuperGlobals->server->get['SCRIPT_NAME']
        ]);
        return $this->setStringIntoTag(implode('', [
                    $this->setStringIntoTag($this->tApp->gettext('i18n_FieldsetLabel_Inputs'), 'legend'),
                    $frm
                        ]), 'fieldset', ['style' => 'float: left;']);
    }

    private function setFormInputSelectPC()
    {
        $choices = [
            $this->tApp->gettext('i18n_Form_Label_CatholicEasterFree_ChoiceNo'),
            $this->tApp->gettext('i18n_Form_Label_CatholicEasterFree_ChoiceYes'),
        ];
        return $this->setArrayToSelect($choices, $this->tCmnSuperGlobals->get('pc'), 'pc', ['size' => 1]);
    }

    private function setFormInputSelectPI()
    {
        $temp2 = [];
        for ($counter = 0; $counter <= 4; $counter++) {
            $temp2[$counter] = $counter . ($counter == 4 ? '+' : '');
        }
        return $this->setArrayToSelect($temp2, $this->tCmnSuperGlobals->get('pi'), 'pi', ['size' => 1]);
    }

    private function setFormInputSelectYM()
    {
        $temp = [];
        for ($counter = date('Y'); $counter >= 2001; $counter--) {
            for ($counter2 = 12; $counter2 >= 1; $counter2--) {
                $crtDate = mktime(0, 0, 0, $counter2, 1, $counter);
                if ($crtDate <= mktime(0, 0, 0, date('m'), 1, date('Y'))) {
                    $temp[$crtDate] = strftime('%Y, %m (%B)', $crtDate);
                }
            }
        }
        return $this->setArrayToSelect($temp, $this->tCmnSuperGlobals->get('ym'), 'ym', ['size' => 1]);
    }

    private function setFormInputText($inName, $inSize, $inAfterLabel)
    {
        $inputParameters = [
            'type'      => 'text',
            'name'      => $inName,
            'value'     => $this->tCmnSuperGlobals->get($inName),
            'size'      => $inSize,
            'maxlength' => $inSize,
        ];
        return $this->setStringIntoShortTag('input', $inputParameters) . ' ' . $inAfterLabel;
    }

    private function setFormOutput($aryStngs)
    {
        $sReturn   = [];
        $overtime  = $this->getOvertimes($aryStngs['Monthly Average Working Hours']);
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
        $brut      += $_REQUEST['afet'] * pow(10, 4);
        $amount    = $this->getValues($brut, $aryStngs);
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
        setlocale(LC_TIME, explode('_', $this->tCmnSession->get('lang'))[0]);
        $crtMonth  = strftime('%B', $this->tCmnSuperGlobals->get('ym'));
        $legend    = sprintf($this->tApp->gettext('i18n_FieldsetLabel_Results')
                . '', $crtMonth, date('Y', $this->tCmnSuperGlobals->get('ym')));
        return $this->setStringIntoTag(implode('', [
                    $this->setStringIntoTag($legend, 'legend'),
                    $this->setStringIntoTag(implode('', $sReturn), 'table')
                        ]), 'fieldset', ['style' => 'float: left;']);
    }

    private function setFormRow($text, $value, $type = 'amount')
    {
        $a                 = '';
        $defaultCellStyle  = $this->setFormatRow($text, $value);
        $defaultCellStyle2 = [];
        switch ($type) {
            case 'amount':
                $value                      = $value / pow(10, 4);
                $defaultCellStyle2['style'] = $defaultCellStyle['style'] . 'text-align:right;';
                $cellValue                  = [];
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

    private function setFormatRow($text, $value)
    {
        $defaultCellStyle = [
            'class' => 'labelS',
        ];
        $fieldsStyled     = $this->buildArrayOfFieldsStyled();
        if (array_key_exists($text, $fieldsStyled)) {
            $defaultCellStyle['style'] = $this->buildStyleForCellFormat($fieldsStyled[$text]);
        }
        if ((is_numeric($value)) && ($value == 0)) {
            $defaultCellStyle['style'] = 'color:#666;';
        }
        return $defaultCellStyle;
    }

    private function setHeaderHtml()
    {
        $headerParameters = [
            'lang'  => str_replace('_', '-', $this->tCmnSession->get('lang')),
            'title' => $this->tApp->gettext('i18n_ApplicationName'),
            'css'   => [
                'vendor/components/flag-icon-css/css/flag-icon.min.css',
                'Salariu/css/salariu.css',
            ],
        ];
        return $this->setHeaderCommon($headerParameters)
                . '<h1>' . $this->tApp->gettext('i18n_ApplicationName') . '</h1>';
    }

    private function setLabel($labelId)
    {
        $labelInfo = $this->appFlags['FI'][$labelId]['Label'];
        $sReturn   = '';
        if (is_array($labelInfo)) {
            if (count($labelInfo) == 3) {
                $pieces  = [
                    $this->tApp->gettext($labelInfo[0]),
                    $this->tApp->gettext($labelInfo[1]),
                ];
                $sReturn = sprintf($pieces[0], $pieces[1], $labelInfo[2]);
            }
        } elseif (is_string($labelInfo)) {
            $sReturn = $this->tApp->gettext($labelInfo);
        }
        return $sReturn;
    }
}
