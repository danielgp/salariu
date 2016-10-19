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
        \danielgp\salariu\FormattingSalariu,
        \danielgp\salariu\Bonuses,
        \danielgp\salariu\ForeignCurrency,
        \danielgp\salariu\Taxation;

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
        echo $this->setFormOutput($configPath, $interfaceElements['Short Labels']);
        echo $this->setFooterHtml($interfaceElements['Application']);
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

    private function getValues($lngBase, $aStngs, $shLabels)
    {
        $inDate             = $this->tCmnSuperGlobals->get('ym');
        $inDT               = new \DateTime(date('Y/m/d', $inDate));
        $wkDay              = $this->setWorkingDaysInMonth($inDT, $this->tCmnSuperGlobals->get('pc'));
        $aReturn            = $this->getValuesPrimary($inDate, $wkDay, $lngBase, $aStngs, $shLabels);
        $pdV                = [
            ($lngBase + $aReturn['ba']),
            $this->tCmnSuperGlobals->get('pi'),
        ];
        $aReturn['pd']      = $this->setPersonalDeduction($inDate, $pdV[0], $pdV[1], $aStngs['Personal Deduction']);
        $aryDeductions      = [
            $this->txLvl['cas'],
            $this->txLvl['snt'],
            $this->txLvl['smj'],
            $aReturn['pd'],
        ];
        $aReturn['impozit'] = $this->getIncomeTaxValue($inDate, $lngBase, $aReturn['ba'], $aryDeductions, $aStngs);
        return $aReturn;
    }

    private function getValuesPrimary($inDate, $wkDay, $lngBase, $aStngs, $shLbl)
    {
        $this->setHealthFundTax($inDate, $lngBase, $aStngs[$shLbl['HFP']], $aStngs[$shLbl['HFUL']]);
        $this->setHealthTax($inDate, $lngBase, $aStngs[$shLbl['HTP']]);
        $nMealDays        = ($wkDay - $this->tCmnSuperGlobals->get('zfb'));
        $unemploymentBase = $lngBase;
        if ($this->tCmnSuperGlobals->get('ym') < mktime(0, 0, 0, 1, 1, 2008)) {
            $unemploymentBase = $this->tCmnSuperGlobals->get('sn');
        }
        $this->setUnemploymentTax($inDate, $unemploymentBase);
        return [
            'ba'   => $this->setFoodTicketsValue($inDate, $aStngs[$shLbl['MTV']]) * $nMealDays,
            'gbns' => $this->tCmnSuperGlobals->get('gbns') * pow(10, 4),
            'zile' => $wkDay,
        ];
    }

    private function setFormInput()
    {
        $sReturn     = $this->setFormInputElements();
        $btn         = $this->setStringIntoShortTag('input', [
            'type'  => 'submit',
            'id'    => 'submit',
            'value' => $this->tApp->gettext('i18n_Form_Button_Recalculate')
        ]);
        $sReturn[]   = $this->setFormRow($this->setLabel('fd'), $btn, 1) . '</tbody>';
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
        $sReturn[] = '<thead><tr><th>' . $this->tApp->gettext('i18n_Form_Label_InputElements')
            . '</th><th>' . $this->tApp->gettext('i18n_Form_Label_InputValues') . '</th></tr></thead><tbody>';
        $sReturn[] = $this->setFormRow($this->setLabel('ym'), $this->setFormInputSelectYM(), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('sn'), $this->setFormInputText('sn', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('sc'), $this->setFormInputText('sc', 2, '%'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pb'), $this->setFormInputText('pb', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pn'), $this->setFormInputText('pn', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('os175'), $this->setFormInputText('os175', 2, 'ore'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('os200'), $this->setFormInputText('os200', 2, 'ore'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pi'), $this->setFormInputSelectPI(), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pc'), $this->setFormInputSelectPC(), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('szamnt'), $this->setFormInputText('szamnt', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('zfb'), $this->setFormInputText('zfb', 2, 'zile'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('gbns'), $this->setFormInputText('gbns', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('afet'), $this->setFormInputText('afet', 10, 'RON'), 1);
        return $sReturn;
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

    private function setFormOutput($configPath, $shLabels)
    {
        $aryStngs    = $this->readTypeFromJsonFileUniversal($configPath, 'valuesToCompute');
        $sReturn     = [];
        $sReturn[]   = $this->setFormOutputHeader();
        $ovTimeVal   = $this->getOvertimes($aryStngs['Monthly Average Working Hours']);
        $additions   = $this->tCmnSuperGlobals->get('pb') + $ovTimeVal['os175'] + $ovTimeVal['os200'];
        $bComponents = [
            'sc' => $this->tCmnSuperGlobals->get('sc'),
            'sn' => $this->tCmnSuperGlobals->get('sn'),
        ];
        $brut        = ($bComponents['sn'] * (1 + $bComponents['sc'] / 100) + $additions) * pow(10, 4);
        $xDate       = '<span style="font-size:smaller;">' . date('d.m.Y', $this->currencyDetails['CXD']) . '</span>';
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('xrate@Date'), $xDate, 10000000);
        $snValue     = $this->tCmnSuperGlobals->get('sn') * 10000;
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('sn'), '&nbsp;', $snValue);
        $scValue     = $this->tCmnSuperGlobals->get('sc');
        $prima       = $this->tCmnSuperGlobals->get('sn') * $scValue * 100;
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('sc'), $scValue . '%', $prima);
        $pbValue     = $this->tCmnSuperGlobals->get('pb') * 10000;
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('pb'), '&nbsp;', $pbValue);
        $ovTime      = [
            'm' => $this->setLabel('ovAmount'),
            1   => $this->tApp->gettext('i18n_Form_Label_OvertimeChoice1'),
            2   => $this->tApp->gettext('i18n_Form_Label_OvertimeChoice2'),
            11  => $ovTimeVal['os175'] * pow(10, 4),
            22  => $ovTimeVal['os200'] * pow(10, 4),
        ];
        $sReturn[]   = $this->setFormRowTwoLabels(sprintf($ovTime['m'], $ovTime[1]), '175%', $ovTime[11]);
        $sReturn[]   = $this->setFormRowTwoLabels(sprintf($ovTime['m'], $ovTime[2]), '200%', $ovTime[22]);
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('sb'), '&nbsp;', $brut);
        $brut        += $this->tCmnSuperGlobals->get('afet') * pow(10, 4);
        $amount      = $this->getValues($brut, $aryStngs, $shLabels);
        $casValue    = $this->txLvl['cas'];
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('cas'), $this->txLvl['casP'] . '%', $casValue);
        $smjValue    = $this->txLvl['smj'];
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('somaj'), $this->txLvl['smjP'] . '%', $this->txLvl['smj']);
        $sntValue    = $this->txLvl['snt'];
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('sanatate'), $this->txLvl['sntP'] . '%', $sntValue);
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('pd'), '&nbsp;', $amount['pd']);
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('impozit'), '&nbsp;', $amount['impozit']);
        $pnValue     = $this->tCmnSuperGlobals->get('pn') * 10000;
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('pn'), '&nbsp;', $pnValue);
        $retineri    = $casValue + $smjValue + $sntValue + $amount['impozit'];
        $net         = $brut - $retineri + $this->tCmnSuperGlobals->get('pn') * 10000;
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('ns'), '&nbsp;', $net);
        $szamntValue = $this->tCmnSuperGlobals->get('szamnt') * 10000;
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('szamnt'), '&nbsp;', $szamntValue);
        $nsc         = $net - $this->tCmnSuperGlobals->get('szamnt') * 10000;
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('nsc'), '&nbsp;', $nsc);
        $fBonus      = [
            'main'   => $this->setLabel('gb'),
            'value'  => $this->tApp->gettext('i18n_Form_Label_FoodBonusesChoiceValue'),
            'mtDays' => ($amount['zile'] - $this->tCmnSuperGlobals->get('zfb')) . '/' . $amount['zile']
        ];
        $fBonusTxt   = sprintf($fBonus['main'], $fBonus['value']);
        $sReturn[]   = $this->setFormRowTwoLabels($fBonusTxt, $fBonus['mtDays'], $amount['ba']);
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('gbns'), '&nbsp;', $amount['gbns']);
        $total       = ($net + $amount['ba'] + $amount['gbns'] - $this->tCmnSuperGlobals->get('szamnt') * 10000);
        $sReturn[]   = $this->setFormRowTwoLabels($this->setLabel('total'), '&nbsp;', $total);
        $sReturn[]   = '</tbody>';
        setlocale(LC_TIME, explode('_', $this->tCmnSession->get('lang'))[0]);
        $crtMonth    = strftime('%B', $this->tCmnSuperGlobals->get('ym'));
        $legentText  = sprintf($this->tApp->gettext('i18n_FieldsetLabel_Results')
            . '', $crtMonth, date('Y', $this->tCmnSuperGlobals->get('ym')));
        $legend      = $this->setStringIntoTag($legentText, 'legend');
        return $this->setStringIntoTag($legend . $this->setStringIntoTag(implode('', $sReturn), 'table'), 'fieldset', [
                'style' => 'float: left;'
        ]);
    }

    private function setFormOutputHeader()
    {
        $sReturn   = [];
        $sReturn[] = '<thead><tr><th>' . $this->tApp->gettext('i18n_Form_Label_ResultedElements') . '</th>';
        $sReturn[] = '<th><i class="fa fa-map-signs floatRight" style="font-size:2em;">&nbsp;</i></th>';
        foreach ($this->currencyDetails['CX'] as $value) {
            $sReturn[] = '<th style="text-align:center;"><span class="flag-icon flag-icon-' . $value['country']
                . '" style="font-size:2em;" title="'
                . $this->tApp->gettext('i18n_CountryName_' . strtoupper($value['country'])) . '">&nbsp;</span></th>';
        }
        return implode('', $sReturn) . '</tr></thead></tbody>';
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

    private function setFormRowTwoLabels($text1, $text2, $value)
    {
        return str_replace(':</td>', ':</td><td class="labelS" style="text-align:right;">'
            . $text2 . '</td>', $this->setFormRow($text1, $value, 'amount'));
    }
}
