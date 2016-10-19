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

trait BasicSalariu
{

    use \danielgp\common_lib\CommonCode;

    protected $appFlags;
    protected $tApp = null;

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
     * To avoid potential language injections from other applications that do not applies here
     *
     * @param type $appSettings
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
