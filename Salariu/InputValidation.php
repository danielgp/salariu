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

trait InputValidation
{

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
        if (date('d') <= 7) {
            $defaultDate = new \DateTime('first day of previous month');
            $maxDate     = new \DateTime('first day of this month');
            $maxDateYM   = new \DateTime('first day of this month');
        }
        return [
            'default'    => $defaultDate,
            'maximum'    => $maxDate,
            'maximumInt' => $maxDate->format('Ymd'),
            'maximumYM'  => $maxDateYM,
            'minimum'    => new \DateTime('2001-01-01'),
        ];
    }

    private function determineCrtMinWage($inMny)
    {
        $lngDate          = $this->tCmnSuperGlobals->request->get('ym');
        $indexArrayValues = 0;
        $intValue         = 0;
        $maxCounter       = count($inMny['EMW']) - 1;
        while (($intValue === 0) && ($indexArrayValues <= $maxCounter)) {
            $crtVal         = $inMny['EMW'][$indexArrayValues];
            $crtDateOfValue = (int) $crtVal['Year'] . ($crtVal['Month'] < 10 ? 0 : '') . $crtVal['Month'] . '01';
            if (($lngDate <= $inMny['YM range']['maximumInt']) && ($lngDate >= $crtDateOfValue)) {
                $intValue = $crtVal['Value'];
            }
            $indexArrayValues++;
        }
        return $intValue;
    }

    private function establishValidValue(\Symfony\Component\HttpFoundation\Request $tCSG, $key, $inMny)
    {
        $validation                      = FILTER_DEFAULT;
        $validOpts                       = [];
        $validOpts['options']['default'] = (in_array($key, ['sm', 'sn']) ? $inMny['MW'] : $inMny['value']);
        switch ($inMny['VFR']['validation_filter']) {
            case "int":
                $inVFR                             = $inMny['VFR']['validation_options'];
                $validOpts['options']['max_range'] = $this->getValidOption($inMny['value'], $inVFR, 'max_range');
                $validOpts['options']['min_range'] = $this->getValidOption($inMny['value'], $inVFR, 'min_range');
                $validation                        = FILTER_VALIDATE_INT;
                break;
            case "float":
                $validOpts['options']['decimal']   = $inMny['VFR']['validation_options']['decimals'];
                $validation                        = FILTER_VALIDATE_FLOAT;
                break;
        }
        $validValue = filter_var($tCSG->get($key), $validation, $validOpts);
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
                ]);
            }
            $tCSG->request->set($key, $validValue);
        }
    }
}
