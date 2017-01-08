<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2017 Daniel Popiniuc
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
        $configPath     = 'Salariu' . DIRECTORY_SEPARATOR . 'config';
        $inElmnts       = $this->readTypeFromJsonFileUniversal($configPath, 'interfaceElements');
        $this->appFlags = [
            'FI'   => $inElmnts['Form Input'],
            'TCAS' => $inElmnts['Table Cell Applied Style'],
            'TCSD' => $inElmnts['Table Cell Style Definitions'],
        ];
        $this->initializeSprGlbAndSession();
        $this->handleLocalizationSalariu($inElmnts['Application']);
        echo $this->setHeaderHtml();
        $this->establishLocalizationToDisplay();
        $dtR            = $this->dateRangesInScope();
        $ymValues       = $this->buildYMvalues($dtR);
        $arySts         = $this->readTypeFromJsonFileUniversal($configPath, 'valuesToCompute');
        echo $this->setFormInput($dtR, $ymValues, $arySts['Minimum Wage'], $inElmnts['Values Filter Rules']);
        echo $this->setFormOutput($dtR, $arySts, $inElmnts);
        echo $this->setFooterHtml($inElmnts['Application']);
    }

    private function getOvertimes($aryStngs)
    {
        $pcToBoolean = [0 => true, 1 => false];
        $pcBoolean   = $pcToBoolean[$this->tCmnSuperGlobals->request->get('pc')];
        $ymVal       = (string) $this->tCmnSuperGlobals->request->get('ym');
        $snVal       = $this->tCmnSuperGlobals->request->get('sn');
        $mnth        = $this->setMonthlyAverageWorkingHours($ymVal, $aryStngs, $pcBoolean);
        return [
            'os175' => ceil($this->tCmnSuperGlobals->request->get('os175') * 1.75 * $snVal / $mnth),
            'os200' => ceil($this->tCmnSuperGlobals->request->get('os200') * 2 * $snVal / $mnth),
        ];
    }

    private function getValues($dtR, $lngBase, $aStngs, $shLabels)
    {
        $inDate             = $this->tCmnSuperGlobals->request->get('ym');
        $aReturn            = $this->getValuesPrimary($dtR, $inDate, $lngBase, $aStngs, $shLabels);
        $pdV                = [
            ($lngBase + $aReturn['ba']),
            $this->tCmnSuperGlobals->request->get('pi'),
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

    private function getValuesPrimary($dtR, $inDate, $lngBase, $aStngs, $shLbl)
    {
        $this->setHealthFundTax($inDate, $lngBase, $aStngs[$shLbl['HFP']], $aStngs[$shLbl['HFUL']]);
        $this->setHealthTax($inDate, $lngBase, $aStngs[$shLbl['HTP']], $aStngs[$shLbl['HFUL']]);
        $nMealDays        = $this->tCmnSuperGlobals->request->get('nDays');
        $unemploymentBase = $lngBase;
        if ($this->tCmnSuperGlobals->request->get('ym') < 20080101) {
            $unemploymentBase = $this->tCmnSuperGlobals->request->get('sn');
        }
        $this->setUnemploymentTax($inDate, $unemploymentBase);
        return [
            'b1'   => $this->setFoodTicketsValue($dtR, $inDate, $aStngs[$shLbl['MTV']]),
            'ba'   => $this->setFoodTicketsValue($dtR, $inDate, $aStngs[$shLbl['MTV']]) * $nMealDays,
            'gbns' => $this->tCmnSuperGlobals->request->get('gbns') * pow(10, 4),
        ];
    }

    private function getWorkingDays()
    {
        $components = [
            \DateTime::createFromFormat('Ymd', $this->tCmnSuperGlobals->request->get('ym')),
            $this->tCmnSuperGlobals->request->get('pc'),
        ];
        $this->tCmnSuperGlobals->request->set('wkDays', $this->setWorkingDaysInMonth($components[0], $components[1]));
        $vDays      = $this->tCmnSuperGlobals->request->get('wkDays') - $this->tCmnSuperGlobals->request->get('zfb');
        $this->tCmnSuperGlobals->request->set('nDays', max($vDays, 0));
    }

    private function setFormInput($dtR, $ymValues, $inMW, $inVFR)
    {
        $this->applyYMvalidations($this->tCmnSuperGlobals, $ymValues, $dtR);
        $minWage   = $this->determineCrtMinWage($this->tCmnSuperGlobals, [
            'EMW'      => $inMW,
            'YM range' => $dtR
        ]);
        $this->processFormInputDefaults($this->tCmnSuperGlobals, [
            'VFR'               => $inVFR,
            'Year Month Values' => $ymValues,
            'MW'                => $minWage,
            'YM range'          => $dtR,
        ]);
        $sReturn   = $this->setFormInputElements($ymValues, $minWage);
        $sReturn[] = $this->setFormInputBottom();
        $frm       = $this->setStringIntoTag($this->setStringIntoTag(implode('', $sReturn), 'table'), 'form', [
            'method' => 'get',
            'action' => $this->tCmnSuperGlobals->getScriptName()
        ]);
        return $this->setFormInputIntoFieldSet($frm);
    }

    private function setFormInputBottom()
    {
        $btn = $this->setStringIntoShortTag('input', [
            'type'  => 'submit',
            'id'    => 'submit',
            'value' => $this->tApp->gettext('i18n_Form_Button_Recalculate')
        ]);
        return $this->setFormRow($this->setLabel('fd'), $btn, 1) . '</tbody>';
    }

    private function setFormInputElements($ymValues, $crtMinWage)
    {
        $sReturn   = [];
        $sReturn[] = '<thead><tr><th>' . $this->tApp->gettext('i18n_Form_Label_InputElements')
            . '</th><th>' . $this->tApp->gettext('i18n_Form_Label_InputValues') . '</th></tr></thead><tbody>';
        $sReturn[] = $this->setFormRow($this->setLabel('ym'), $this->setFormInputSelectYM($ymValues), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('sm'), $this->setFormInputText('sm', 10, 'RON', $crtMinWage), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('sn'), $this->setFormInputText('sn', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('sc'), $this->setFormInputText('sc', 7, '%'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pb'), $this->setFormInputText('pb', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pn'), $this->setFormInputText('pn', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('os175'), $this->setFormInputText('os175', 2, 'ore'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('os200'), $this->setFormInputText('os200', 2, 'ore'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pi'), $this->setFormInputSelectPI(), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('pc'), $this->setFormInputSelectPC(), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('szamnt'), $this->setFormInputText('szamnt', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('zfb'), $this->setFormInputText('zfb', 2, 'zile'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('zfs'), $this->setFormInputText('zfs', 2, 'zile'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('gbns'), $this->setFormInputText('gbns', 10, 'RON'), 1);
        $sReturn[] = $this->setFormRow($this->setLabel('afet'), $this->setFormInputText('afet', 10, 'RON'), 1);
        return $sReturn;
    }

    private function setFormInputIntoFieldSet($frm)
    {
        $aryFieldSet = [
            $this->setStringIntoTag($this->tApp->gettext('i18n_FieldsetLabel_Inputs'), 'legend'),
            $frm
        ];
        return $this->setStringIntoTag(implode('', $aryFieldSet), 'fieldset', ['style' => 'float: left;']);
    }

    private function setFormInputText($inName, $inSize, $inAfterLabel, $crtMinWage = 0)
    {
        $inputParameters = [
            'type'      => 'text',
            'name'      => $inName,
            'value'     => $this->tCmnSuperGlobals->request->get($inName),
            'size'      => $inSize,
            'maxlength' => $inSize,
        ];
        if ($inName == 'sm') {
            $inputParameters['readonly'] = 'readonly';
            $inputParameters['value']    = $crtMinWage;
            $this->tCmnSuperGlobals->request->set('sm', $crtMinWage);
        }
        return $this->setStringIntoShortTag('input', $inputParameters) . ' ' . $inAfterLabel;
    }

    private function setFormOutput($dtR, $aryStngs, $inElements)
    {
        $sReturn     = [];
        $this->setExchangeRateValues($inElements['Application'], $inElements['Relevant Currencies']);
        $sReturn[]   = $this->setFormOutputHeader();
        $ovTimeVal   = $this->getOvertimes($aryStngs['Monthly Average Working Hours']);
        $additions   = $this->tCmnSuperGlobals->request->get('pb') + $ovTimeVal['os175'] + $ovTimeVal['os200'];
        $this->getWorkingDays();
        $bComponents = [
            'sc'   => $this->tCmnSuperGlobals->request->get('sc'),
            'sn'   => $this->tCmnSuperGlobals->request->get('sn'),
            'zile' => $this->tCmnSuperGlobals->request->get('wkDays'),
        ];
        $xDate       = '<span style="font-size:smaller;">' . date('d.m.Y', $this->currencyDetails['CXD']) . '</span>';
        $sReturn[]   = $this->setFrmRowTwoLbls($this->setLabel('xrate@Date'), $xDate, pow(10, 7));
        $snValue     = $this->tCmnSuperGlobals->request->get('sn') * pow(10, 4);
        $amntLAA     = round(($this->tCmnSuperGlobals->request->get('zfs') / $bComponents['zile']) * $snValue, -4);
        $sReturn[]   = $this->setFormOutputBonuses($snValue, $bComponents['zile'], $amntLAA, $ovTimeVal);
        $brut        = ($bComponents['sn'] * (1 + $bComponents['sc'] / 100) + $additions) * pow(10, 4) - $amntLAA;
        $sReturn[]   = $this->setFrmRowTwoLbls($this->setLabel('sb'), '&nbsp;', $brut);
        $brut2       = $brut + $this->tCmnSuperGlobals->request->get('afet') * pow(10, 4);
        $amnt        = $this->getValues($dtR, $brut2, $aryStngs, $inElements['Short Labels']);
        $sReturn[]   = $this->setFormOutputTaxations($brut2, $amnt);
        $pnValue     = $this->tCmnSuperGlobals->request->get('pn') * pow(10, 4);
        $sReturn[]   = $this->setFrmRowTwoLbls($this->setLabel('pn'), '&nbsp;', $pnValue);
        $retineri    = $this->txLvl['cas'] + $this->txLvl['smj'] + $this->txLvl['snt'] + $amnt['impozit'];
        $net         = $brut2 - $retineri + $this->tCmnSuperGlobals->request->get('pn') * pow(10, 4);
        $sReturn[]   = $this->setFrmRowTwoLbls($this->setLabel('ns'), '&nbsp;', $net);
        $szamntValue = $this->tCmnSuperGlobals->request->get('szamnt') * pow(10, 4);
        $sReturn[]   = $this->setFrmRowTwoLbls($this->setLabel('szamnt'), '&nbsp;', $szamntValue);
        $nsc         = $net - $this->tCmnSuperGlobals->request->get('szamnt') * pow(10, 4);
        $sReturn[]   = $this->setFrmRowTwoLbls($this->setLabel('nsc'), '&nbsp;', $nsc);
        $fBonus      = [
            'main'   => $this->setLabel('gb'),
            'value'  => $this->tApp->gettext('i18n_Form_Label_FoodBonusesChoiceValue'),
            'mtDays' => $this->tCmnSuperGlobals->request->get('nDays') . '&nbsp;/&nbsp;' . $bComponents['zile']
        ];
        $fBonusTxt   = sprintf($fBonus['main'], $fBonus['value']);
        $sReturn[]   = $this->setFrmRowTwoLbls($fBonusTxt, $fBonus['mtDays'], $amnt['ba']);
        $sReturn[]   = $this->setFrmRowTwoLbls($this->setLabel('gbns'), '&nbsp;', $amnt['gbns']);
        $total       = ($net + $amnt['ba'] + $amnt['gbns'] - $this->tCmnSuperGlobals->request->get('szamnt') * 10000);
        $sReturn[]   = $this->setFrmRowTwoLbls($this->setLabel('total'), '&nbsp;', $total);
        $sReturn[]   = '</tbody>';
        $tDate       = \DateTime::createFromFormat('Ymd', $this->tCmnSuperGlobals->request->get('ym'));
        $legentText  = sprintf($this->tApp->gettext('i18n_FieldsetLabel_Results')
            . '', strftime('%B', mktime(0, 0, 0, $tDate->format('n'), 1, $tDate->format('Y'))), $tDate->format('Y'));
        $fieldsetC   = $this->setStringIntoTag($legentText, 'legend')
            . $this->setStringIntoTag(implode('', $sReturn), 'table');
        return $this->setStringIntoTag($fieldsetC, 'fieldset', [
                'style' => 'float: left;'
        ]);
    }

    private function setFormOutputBonuses($snValue, $wkDays, $amntLAA, $ovTimeVal)
    {
        $sRt      = [];
        $sMin     = $this->tCmnSuperGlobals->request->get('sm') * pow(10, 4);
        $sRt[]    = $this->setFrmRowTwoLbls($this->setLabel('sm'), '&nbsp;', $sMin);
        $sRt[]    = $this->setFrmRowTwoLbls($this->setLabel('sn'), '&nbsp;', $snValue);
        $scValue  = $this->tCmnSuperGlobals->request->get('sc');
        $prima    = $this->tCmnSuperGlobals->request->get('sn') * $scValue * 100;
        $sRt[]    = $this->setFrmRowTwoLbls($this->setLabel('sc'), $scValue . '%', $prima);
        $pbValue  = $this->tCmnSuperGlobals->request->get('pb') * pow(10, 4);
        $sRt[]    = $this->setFrmRowTwoLbls($this->setLabel('pb'), '&nbsp;', $pbValue);
        $ovTime   = [
            'o1' => $this->tCmnSuperGlobals->request->get('os175'),
            'o2' => $this->tCmnSuperGlobals->request->get('os200'),
        ];
        $sRt[]    = $this->setFrmRowTwoLbls($this->setLabel('ovAmount1'), '<span style="font-size:smaller;">'
            . $ovTime['o1'] . 'h&nbsp;x&nbsp;175%</span>', $ovTimeVal['os175'] * pow(10, 4));
        $sRt[]    = $this->setFrmRowTwoLbls($this->setLabel('ovAmount2'), '<span style="font-size:smaller;">'
            . $ovTime['o2'] . 'h&nbsp;x&nbsp;200%</span>', $ovTimeVal['os200'] * pow(10, 4));
        $fLeaveAA = $this->tCmnSuperGlobals->request->get('zfs') . '&nbsp;/&nbsp;' . $wkDays;
        $sRt[]    = $this->setFrmRowTwoLbls($this->setLabel('zfsa'), $fLeaveAA, $amntLAA);
        return implode('', $sRt);
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

    private function setFormOutputTaxations($brut, $amnt)
    {
        $sRn              = [];
        $limitDisplayBase = false;
        if ($brut > $this->txLvl['casP_base']) {
            $limitDisplayBase = true;
            $sRn[]            = $this->setFrmRowTwoLbls($this->setLabel('cas_base'), '', $this->txLvl['casP_base']);
        }
        $sRn[] = $this->setFrmRowTwoLbls($this->setLabel('cas'), $this->txLvl['casP'] . '%', $this->txLvl['cas']);
        $sRn[] = $this->setFrmRowTwoLbls($this->setLabel('somaj'), $this->txLvl['smjP'] . '%', $this->txLvl['smj']);
        if ($limitDisplayBase && array_key_exists('sntP_base', $this->txLvl)) {
            $sRn[] = $this->setFrmRowTwoLbls($this->setLabel('sntP_base'), '', $this->txLvl['sntP_base']);
        }
        $sRn[] = $this->setFrmRowTwoLbls($this->setLabel('sanatate'), $this->txLvl['sntP'] . '%', $this->txLvl['snt']);
        $sRn[] = $this->setFrmRowTwoLbls($this->setLabel('pd'), '&nbsp;', $amnt['pd']);
        $sRn[] = $this->setFrmRowTwoLbls($this->setLabel('impozit_base'), '&nbsp;', $this->txLvl['inTaxP_base']);
        $sRn[] = $this->setFrmRowTwoLbls($this->setLabel('impozit'), $this->txLvl['inTaxP'] . '%', $amnt['impozit']);
        return implode('', $sRn);
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

    private function setFrmRowTwoLbls($text1, $text2, $value)
    {
        return str_replace(':</td>', ':</td><td class="labelS" style="text-align:right;">'
            . $text2 . '</td>', $this->setFormRow($text1, $value, 'amount'));
    }
}
