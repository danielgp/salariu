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

trait FormattingSalariu
{

    use \danielgp\salariu\BasicSalariu;

    private function buildArrayOfFieldsStyled()
    {
        $sReturn = [];
        foreach ($this->appFlags['TCAS'] as $key => $value) {
            $sReturn[$this->tApp->gettext($key)]       = $value;
            $sReturn[$this->tApp->gettext($key) . ':'] = $value;
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

    private function setFormInputSelectYM($ymValues)
    {
        return $this->setArrayToSelect($ymValues, $this->tCmnSuperGlobals->get('ym'), 'ym', ['size' => 1]);
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
}
