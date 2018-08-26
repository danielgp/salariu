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

trait InputValidation
{

    private function applyCurrencyValidations(\Symfony\Component\HttpFoundation\Request $tCSG, $defaults, $allAllowed)
    {
        $valuesCurrency = $tCSG->get('xMoney'); // 0 or 1 or multiple values
        if (is_array($valuesCurrency)) {
            $tCSG->request->set('xMoney', array_intersect($valuesCurrency, $allAllowed));
        }
        if (is_null($valuesCurrency)) {
            $tCSG->request->set('xMoney', $defaults);
        }
    }

    private function applyYMvalidations(\Symfony\Component\HttpFoundation\Request $tCSG, $ymValues, $dtR)
    {
        $validOpt = [
            'options' => [
                'default'   => $dtR['default']->format('Ymd'),
                'max_range' => $dtR['maximum']->format('Ymd'),
                'min_range' => $dtR['minimum']->format('Ymd'),
            ]
        ];
        $validYM  = filter_var($tCSG->get('ym'), FILTER_VALIDATE_INT, $validOpt);
        if (!array_key_exists($validYM, $ymValues)) {
            $validYM = $validOpt['options']['default'];
        }
        $tCSG->request->set('ym', $validYM);
    }

    private function buildYMvalues($dtR)
    {
        $startDate = $dtR['minimum'];
        $endDate   = $dtR['maximumYM'];
        $temp      = [];
        while ($endDate >= $startDate) {
            $temp[$endDate->format('Ymd')] = $endDate->format('Y, m (')
                . strftime('%B', mktime(0, 0, 0, $endDate->format('n'), 1, $endDate->format('Y'))) . ')';
            $endDate->sub(new \DateInterval('P1M'));
        }
        return $temp;
    }

    private function dateRangesInScope()
    {
        $defaultDate = new \DateTime('first day of this month');
        $maxDate     = new \DateTime('first day of next month');
        $maxDateYM   = new \DateTime('first day of next month');
        $maxDate->add(new \DateInterval('P1M'));
        $maxDateYM->add(new \DateInterval('P1M'));
        if (date('d') <= 15) {
            $defaultDate->sub(new \DateInterval('P1M'));
            $maxDate->sub(new \DateInterval('P1M'));
            $maxDateYM->sub(new \DateInterval('P1M'));
        }
        return [
            'default'    => $defaultDate,
            'maximum'    => $maxDate,
            'maximumInt' => $maxDate->format('Ymd'),
            'maximumYM'  => $maxDateYM,
            'minimum'    => new \DateTime('2001-01-01'),
        ];
    }

    private function determineCrtMinWage(\Symfony\Component\HttpFoundation\Request $tCSG, $inMny)
    {
        $lngDate    = $tCSG->get('ym');
        $intValue   = 0;
        $maxCounter = count($inMny['EMW']);
        for ($counter = 0; $counter < $maxCounter; $counter++) {
            $crtVal         = $inMny['EMW'][$counter];
            $crtDV          = \DateTime::createFromFormat('Y-n-j', $crtVal['Year'] . '-' . $crtVal['Month'] . '-1');
            $crtDateOfValue = (int) $crtDV->format('Ymd');
            if (($lngDate <= $inMny['YM range']['maximumInt']) && ($lngDate >= $crtDateOfValue)) {
                $intValue = $crtVal['Value'];
                $counter  = $maxCounter;
            }
        }
        return $intValue;
    }

    private function establishFilterParameters($inMny, $validOpts)
    {
        $validation = FILTER_DEFAULT;
        switch ($inMny['VFR']['validation_filter']) {
            case 'int':
                $inVFR                             = $inMny['VFR']['validation_options'];
                $validation                        = FILTER_VALIDATE_INT;
                $validOpts['options']['max_range'] = $this->getValidOption($inMny['value'], $inVFR, 'max_range');
                $validOpts['options']['min_range'] = $this->getValidOption($inMny['value'], $inVFR, 'min_range');
                break;
            case 'float':
                $validOpts['options']['decimal']   = $inMny['VFR']['validation_options']['decimals'];
                $validation                        = FILTER_VALIDATE_FLOAT;
                break;
        }
        return [
            'validation'         => $validation,
            'validation_options' => $validOpts,
        ];
    }

    private function establishValidValue(\Symfony\Component\HttpFoundation\Request $tCSG, $key, $inMny)
    {
        $validOpts                       = [];
        $validOpts['options']['default'] = $inMny['value'];
        if (in_array($key, ['fb', 'fc'])) {
            $validOpts['options']['default'] = $inMny[$key];
        }
        if (in_array($key, ['sm', 'sn'])) {
            $validOpts['options']['default']                 = $inMny['MW'];
            $inMny['VFR']['validation_options']['min_range'] = $inMny['MW'];
        }
        $vOptions   = $this->establishFilterParameters($inMny, $validOpts);
        $validValue = filter_var($tCSG->get($key), $vOptions['validation'], $vOptions['validation_options']);
        return $validValue;
    }

    private function getValidOption($value, $inValuesFilterRules, $optionLabel)
    {
        $valReturn = $inValuesFilterRules[$optionLabel];
        if ($valReturn == 'default') {
            $valReturn = $value;
        }
        return $valReturn;
    }

    protected function processFormInputDefaults(\Symfony\Component\HttpFoundation\Request $tCSG, $aMultiple)
    {
        foreach ($aMultiple['VFR'] as $key => $value) {
            $validValue = trim($tCSG->get($key));
            if (array_key_exists('validation_options', $value)) {
                $validValue = $this->establishValidValue($tCSG, $key, [
                    'value'    => $aMultiple['VFR'][$key]['default'],
                    'MW'       => $aMultiple['MW'],
                    'VFR'      => $aMultiple['VFR'][$key],
                    'YM range' => $aMultiple['YM range'],
                    'fb'       => $aMultiple['fb'],
                    'fc'       => $aMultiple['fc'],
                ]);
            }
            $tCSG->request->set($key, $validValue);
        }
    }
}
