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

    private function applyYMvalidations()
    {
        $validOpt = [
            'options' => [
                'default'   => mktime(0, 0, 0, date('m'), 1, date('Y')),
                'max_range' => mktime(0, 0, 0, date('m'), 1, date('Y')),
                'min_range' => mktime(0, 0, 0, 1, 1, 2001),
            ]
        ];
        $validYM  = filter_var($this->tCmnSuperGlobals->request->get('ym'), FILTER_VALIDATE_INT, $validOpt);
        $this->tCmnSuperGlobals->request->set('ym', $validYM);
    }

    private function establishValidValue($key, $value, $inValuesFilterRules)
    {
        $validOpts                       = [];
        $validOpts['options']['default'] = $value;
        switch ($inValuesFilterRules['validation_filter']) {
            case "int":
                $inVFR                             = $inValuesFilterRules['validation_options'];
                $validOpts['options']['max_range'] = $this->getValidOption($value, $inVFR, 'max_range');
                $validOpts['options']['min_range'] = $this->getValidOption($value, $inVFR, 'min_range');
                $validation                        = FILTER_VALIDATE_INT;
                break;
            case "float":
                $validOpts['options']['decimal']   = 2;
                $validation                        = FILTER_VALIDATE_FLOAT;
                break;
        }
        $validValue = filter_var($this->tCmnSuperGlobals->get($key), $validation, $validOpts);
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

    private function processFormInputDefaults($inValuesFilterRules)
    {
        $this->applyYMvalidations();
        foreach ($inValuesFilterRules as $key => $value) {
            $validValue = trim($this->tCmnSuperGlobals->get($key));
            if (array_key_exists('validation_options', $value)) {
                $validValue = $this->establishValidValue($key, $value['default'], $value);
            }
            $this->tCmnSuperGlobals->request->set($key, $validValue);
        }
    }
}
