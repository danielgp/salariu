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

    private function applyYMvalidations(\Symfony\Component\HttpFoundation\Request $tCSG, $ymValues)
    {
        $validOpt = [
            'options' => [
                'default'   => mktime(0, 0, 0, date('m'), 1, date('Y')),
                'max_range' => mktime(0, 0, 0, date('m'), 1, date('Y')),
                'min_range' => mktime(0, 0, 0, 1, 1, 2001),
            ]
        ];
        $validYM  = filter_var($tCSG->get('ym'), FILTER_VALIDATE_INT, $validOpt);
        if (!array_key_exists($validYM, $ymValues)) {
            $validYM = mktime(0, 0, 0, date('m'), 1, date('Y'));
        }
        $tCSG->request->set('ym', $validYM);
    }

    private function buildYMvalues()
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
        return $temp;
    }

    private function establishValidValue(\Symfony\Component\HttpFoundation\Request $tCSG, $key, $value, $inVlsFltrRls)
    {
        $validation                      = FILTER_DEFAULT;
        $validOpts                       = [];
        $validOpts['options']['default'] = $value;
        switch ($inVlsFltrRls['validation_filter']) {
            case "int":
                $inVFR                             = $inVlsFltrRls['validation_options'];
                $validOpts['options']['max_range'] = $this->getValidOption($value, $inVFR, 'max_range');
                $validOpts['options']['min_range'] = $this->getValidOption($value, $inVFR, 'min_range');
                $validation                        = FILTER_VALIDATE_INT;
                break;
            case "float":
                $validOpts['options']['decimal']   = 2;
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

    protected function processFormInputDefaults(\Symfony\Component\HttpFoundation\Request $tCSG, $inVFR, $ymValues)
    {
        $this->applyYMvalidations($tCSG, $ymValues);
        foreach ($inVFR as $key => $value) {
            $validValue = trim($tCSG->get($key));
            if (array_key_exists('validation_options', $value)) {
                $validValue = $this->establishValidValue($tCSG, $key, $value['default'], $value);
            }
            $tCSG->request->set($key, $validValue);
        }
    }
}
