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
        \danielgp\salariu\ForeignCurrency,
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
        $this->processFormInputDefaults($interfaceElements['Default Values']);
        echo $this->setFormInput();
        $this->setExchangeRateValues($interfaceElements['Application'], $interfaceElements['Relevant Currencies']);
        echo $this->setFormOutput($configPath);
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

    private function getIncomeTaxValue($inDate, $lngBase, $vBA, $aryDeductions, $arySettings)
    {
        $rest = $lngBase - array_sum($aryDeductions);
        if ($inDate >= mktime(0, 0, 0, 7, 1, 2010)) {
            $rest += round($vBA, -4);
            if ($inDate >= mktime(0, 0, 0, 10, 1, 2010)) {
                $rest += round($this->tCmnSuperGlobals->get('gbns') * pow(10, 4), -4);
            }
        }
        $rest += $this->tCmnSuperGlobals->get('afet') * pow(10, 4);
        return $this->setIncomeTax($inDate, $rest, $arySettings['Income Tax']);
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
        $inDate             = $this->tCmnSuperGlobals->get('ym');
        $aReturn            = $this->getValuesPrimary($inDate, $lngBase, $aStngs);
        $pdVal              = [
            ($lngBase + $aReturn['ba']),
            $this->tCmnSuperGlobals->get('pi'),
            $aStngs['Personal Deduction'],
        ];
        $aReturn['pd']      = $this->setPersonalDeduction($inDate, $pdVal[1], $pdVal[2], $pdVal[3]);
        $aryDeductions      = [
            $aReturn['cas'],
            $aReturn['sanatate'],
            $aReturn['somaj'],
            $aReturn['pd'],
        ];
        $aReturn['impozit'] = $this->getIncomeTaxValue($inDate, $lngBase, $aReturn['ba'], $aryDeductions, $aStngs);
        return $aReturn;
    }

    private function getValuesPrimary($inDate, $lngBase, $aStngs)
    {
        $inDT             = new \DateTime(date('Y/m/d', $inDate));
        $wkDay            = $this->setWorkingDaysInMonth($inDT, $this->tCmnSuperGlobals->get('pc'));
        $nMealDays        = ($wkDay - $this->tCmnSuperGlobals->get('zfb'));
        $shLbl            = [
            'HFP'  => 'Health Fund Percentage',
            'HFUL' => 'Health Fund Upper Limit',
            'HTP'  => 'Health Tax Percentage',
            'MTV'  => 'Meal Ticket Value',
        ];
        $unemploymentBase = $lngBase;
        if ($this->tCmnSuperGlobals->get('ym') < mktime(0, 0, 0, 1, 1, 2008)) {
            $unemploymentBase = $this->tCmnSuperGlobals->get('sn');
        }
        return [
            'ba'       => $this->setFoodTicketsValue($inDate, $aStngs[$shLbl['MTV']]) * $nMealDays,
            'cas'      => $this->setHealthFundTax($inDate, $lngBase, $aStngs[$shLbl['HFP']], $aStngs[$shLbl['HFUL']]),
            'gbns'     => $this->tCmnSuperGlobals->get('gbns') * pow(10, 4),
            'sanatate' => $this->setHealthTax($inDate, $lngBase, $aStngs[$shLbl['HTP']]),
            'somaj'    => $this->setUnemploymentTax($inDate, $unemploymentBase),
            'zile'     => $wkDay,
        ];
    }

    private function handleLocalizationSalariu($appSettings)
    {
        $this->handleLocalizationSalariuInputsIntoSession($appSettings);
        $this->handleLocalizationSalariuSafe($appSettings);
        $localizationFile = 'Salariu/locale/' . $this->tCmnSession->get('lang') . '/LC_MESSAGES/salariu.mo';
        $translations     = new \Gettext\Translations;
        $translations->addFromMoFile($localizationFile);
        $this->tApp       = new \Gettext\Translator();
        $this->tApp->loadTranslations($translations);
    }

    private function handleLocalizationSalariuInputsIntoSession($appSettings)
    {
        if (is_null($this->tCmnSuperGlobals->get('lang')) && is_null($this->tCmnSession->get('lang'))) {
            $this->tCmnSession->set('lang', $appSettings['Default Language']);
        } elseif (!is_null($this->tCmnSuperGlobals->get('lang'))) {
            $this->tCmnSession->set('lang', filter_var($this->tCmnSuperGlobals->get('lang'), FILTER_SANITIZE_STRING));
        }
    }

    /**
     * to avoid potential language injections from other applications that do not applies here
     */
    private function handleLocalizationSalariuSafe($appSettings)
    {
        if (!array_key_exists($this->tCmnSession->get('lang'), $appSettings['Available Languages'])) {
            $this->tCmnSession->set('lang', $appSettings['Default Language']);
        }
    }

    private function processFormInputDefaults($inDefaultValues)
    {
        if (is_null($this->tCmnSuperGlobals->get('ym'))) {
            $this->tCmnSuperGlobals->request->set('ym', mktime(0, 0, 0, date('m'), 1, date('Y')));
        }
        foreach ($inDefaultValues as $key => $value) {
            if (is_null($this->tCmnSuperGlobals->get($key))) {
                $this->tCmnSuperGlobals->request->set($key, $value);
            }
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
        $sReturn     = $this->setFormInputElements();
        $btn         = $this->setStringIntoShortTag('input', [
            'type'  => 'submit',
            'id'    => 'submit',
            'value' => $this->setLabel('bc')
        ]);
        $sReturn[]   = $this->setFormRow($this->setLabel('fd'), $btn, 1);
        $frm         = $this->setStringIntoTag($this->setStringIntoTag(implode('', $sReturn), 'table'), 'form', [
            'method' => 'get',
            'action' => $this->tCmnSuperGlobals->getScriptName()
        ]);
        $aryFieldSet = [
            $this->setStringIntoTag($this->tApp->gettext('i18n_FieldsetLabel_Inputs'), 'legend'),
            $frm
        ];
        return $this->setStringIntoTag(implode('', $aryFieldSet), 'fieldset', ['style' => 'float: left;']);
    }

    private function setFormInputElements()
    {
        $sReturn   = [];
        $sReturn[] = $this->setFormRow($this->setLabel('ym'), $this->setFormInputSelectYM(), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('sn'), $this->setFormInputText('sn', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('sc'), $this->setFormInputText('sc', 2, '%'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pb'), $this->setFormInputText('pb', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pn'), $this->setFormInputText('pn', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('os175'), $this->setFormInputText('os175', 2, ''), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('os200'), $this->setFormInputText('os200', 2, ''), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pi'), $this->setFormInputSelectPI(), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pc'), $this->setFormInputSelectPC(), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('szamnt'), $this->setFormInputText('szamnt', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('zfb'), $this->setFormInputText('zfb', 2, ''), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('gbns'), $this->setFormInputText('gbns', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('afet'), $this->setFormInputText('afet', 10, 'RON'), 1);
        return $sReturn;
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

    private function setFormOutput($configPath)
    {
        $aryStngs    = $this->readTypeFromJsonFileUniversal($configPath, 'valuesToCompute');
        $sReturn     = [];
        $ovTimeVal   = $this->getOvertimes($aryStngs['Monthly Average Working Hours']);
        $additions   = $this->tCmnSuperGlobals->get('pb') + $ovTimeVal['os175'] + $ovTimeVal['os200'];
        $bComponents = [
            'sc' => $this->tCmnSuperGlobals->get('sc'),
            'sn' => $this->tCmnSuperGlobals->get('sn'),
        ];
        $brut        = ($bComponents['sn'] * (1 + $bComponents['sc'] / 100) + $additions) * pow(10, 4);
        $text        = $this->tApp->gettext('i18n_Form_Label_ExchangeRateAtDate');
        $xRate       = str_replace('%1', date('d.m.Y', $this->currencyDetails['CXD']), $text);
        $sReturn[]   = $this->setFormRow($xRate, 10000000);
        $text        = $this->tApp->gettext('i18n_Form_Label_NegotiatedSalary');
        $sReturn[]   = $this->setFormRow($text, $this->tCmnSuperGlobals->get('sn') * 10000);
        $prima       = $this->tCmnSuperGlobals->get('sn') * $this->tCmnSuperGlobals->get('sc') * 100;
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_CumulatedAddedValue'), $prima);
        $text        = $this->tApp->gettext('i18n_Form_Label_AdditionalBruttoAmount');
        $sReturn[]   = $this->setFormRow($text, $this->tCmnSuperGlobals->get('pb') * 10000);
        $ovTime      = [
            'm' => $this->tApp->gettext('i18n_Form_Label_OvertimeAmount'),
            1   => $this->tApp->gettext('i18n_Form_Label_OvertimeChoice1'),
            2   => $this->tApp->gettext('i18n_Form_Label_OvertimeChoice2'),
        ];
        $sReturn[]   = $this->setFormRow(sprintf($ovTime['m'], $ovTime[1], '175%'), ($ovTimeVal['os175'] * pow(10, 4)));
        $sReturn[]   = $this->setFormRow(sprintf($ovTime['m'], $ovTime[2], '200%'), ($ovTimeVal['os200'] * pow(10, 4)));
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_BruttoSalary'), $brut);
        $brut        += $this->tCmnSuperGlobals->get('afet') * pow(10, 4);
        $amount      = $this->getValues($brut, $aryStngs);
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_PensionFund'), $amount['cas']);
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_UnemploymentTax'), $amount['somaj']);
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_HealthTax'), $amount['sanatate']);
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_PersonalDeduction'), $amount['pd']);
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_ExciseTax'), $amount['impozit']);
        $retineri    = $amount['cas'] + $amount['somaj'] + $amount['sanatate'] + $amount['impozit'];
        $net         = $brut - $retineri + $this->tCmnSuperGlobals->get('pn') * 10000;
        $text        = $this->tApp->gettext('i18n_Form_Label_AdditionalNettoAmount');
        $sReturn[]   = $this->setFormRow($text, $this->tCmnSuperGlobals->get('pn') * 10000);
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_NettoSalary'), $net);
        $text        = $this->tApp->gettext('i18n_Form_Label_SeisureAmout');
        $sReturn[]   = $this->setFormRow($text, $this->tCmnSuperGlobals->get('szamnt') * 10000);
        $text        = $this->tApp->gettext('i18n_Form_Label_NettoSalaryCash');
        $sReturn[]   = $this->setFormRow($text, ($net - $this->tCmnSuperGlobals->get('szamnt') * 10000));
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_WorkingDays'), $amount['zile'], 'value');
        $fBonus      = [
            'main'   => $this->tApp->gettext('i18n_Form_Label_FoodBonuses'),
            'no'     => $this->tApp->gettext('i18n_Form_Label_FoodBonusesChoiceNo'),
            'value'  => $this->tApp->gettext('i18n_Form_Label_FoodBonusesChoiceValue'),
            'mtDays' => ($amount['zile'] - $this->tCmnSuperGlobals->get('zfb'))
        ];
        $fBonusTxt   = sprintf($fBonus['main'], $fBonus['value'], $fBonus['no'], $fBonus['mtDays']);
        $sReturn[]   = $this->setFormRow($fBonusTxt, $amount['ba']);
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_FoodBonusesValue'), $amount['gbns']);
        $total       = ($net + $amount['ba'] + $amount['gbns'] - $this->tCmnSuperGlobals->get('szamnt') * 10000);
        $sReturn[]   = $this->setFormRow($this->tApp->gettext('i18n_Form_Label_Total'), $total);
        setlocale(LC_TIME, explode('_', $this->tCmnSession->get('lang'))[0]);
        $crtMonth    = strftime('%B', $this->tCmnSuperGlobals->get('ym'));
        $legend      = sprintf($this->tApp->gettext('i18n_FieldsetLabel_Results')
            . '', $crtMonth, date('Y', $this->tCmnSuperGlobals->get('ym')));
        return $this->setStringIntoTag(implode('', [
                $this->setStringIntoTag($legend, 'legend'),
                $this->setStringIntoTag(implode('', $sReturn), 'table')
                ]), 'fieldset', ['style' => 'float: left;']);
    }

    private function setFormRow($text, $value, $type = 'amount')
    {
        $defaultCellStyle = $this->setFormatRow($text, $value);
        switch ($type) {
            case 'amount':
                $value2show = $this->setFormRowAmount(($value / pow(10, 4)), $defaultCellStyle);
                break;
            case 'value':
                $value2show = $this->setStringIntoTag($value, 'td', array_merge($defaultCellStyle, [
                    'colspan' => count($this->currencyDetails['CX'])
                ]));
                break;
            default:
                $value2show = $this->setStringIntoTag($value, 'td');
                break;
        }
        return $this->setStringIntoTag($this->setStringIntoTag($text, 'td', $defaultCellStyle) . $value2show, 'tr');
    }

    private function setFormRowAmount($value, $defaultCellStyle)
    {
        $cellValue                 = [];
        $defaultCellStyle['style'] .= 'text-align:right;';
        foreach ($this->currencyDetails['CX'] as $key2 => $value2) {
            $fmt         = new \NumberFormatter($value2['locale'], \NumberFormatter::CURRENCY);
            $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $value2['decimals']);
            $finalValue  = $fmt->formatCurrency($value / $this->currencyDetails['CXV'][$key2], $key2);
            $cellValue[] = $this->setStringIntoTag($finalValue, 'td', $defaultCellStyle);
        }
        return implode('', $cellValue);
    }

    private function setFormatRow($text, $value)
    {
        $defaultCellStyle = [
            'class' => 'labelS',
            'style' => 'color:#000;',
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
        return $this->setLabelSuffix($sReturn);
    }

    private function setLabelSuffix($text)
    {
        $suffix = '';
        if (!in_array($text, ['', '&nbsp;']) && (strpos($text, '<input') === false) && (substr($text, -1) !== '!')) {
            $suffix = ':';
        }
        return $text . $suffix;
    }
}
