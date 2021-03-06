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

trait BasicSalariu
{

    use \danielgp\common_lib\CommonCode;

    protected $appFlags;
    protected $tApp = null;

    private function handleLocalizationSalariu($appSettings)
    {
        $this->handleLocalizationSalariuInputsIntoSession($appSettings);
        $this->handleLocalizationSalariuSafe($appSettings);
        $localizationFile = 'static/locale/' . $this->tCmnSession->get('lang') . '/LC_MESSAGES/salariu.mo';
        $translations     = new \Gettext\Translations();
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

    private function setAnalyticsToInclude()
    {
        if (file_exists('analytics.min.html')) {
            return file_get_contents('analytics.min.html');
        }
        return '';
    }

    private function setFooterHtml($appSettings)
    {
        return $this->setFooterCommon(''
                . $this->setUpperRightBoxLanguages($appSettings['Available Languages'])
                . '<div class="resetOnly author">&copy; ' . date('Y') . ' '
                . $appSettings['Copyright Holder'] . '</div>'
                . '<hr/>'
                . $this->setAnalyticsToInclude()
                . '<div class="disclaimer">'
                . $this->tApp->gettext('i18n_Disclaimer')
                . '</div>');
    }

    private function setHeaderHtml()
    {
        $headerParameters = [
            'css'        => [
                'vendor/components/flag-icon-css/css/flag-icon.min.css',
                'vendor/fortawesome/font-awesome/css/font-awesome.min.css',
                'static/css/salariu.css',
            ],
            'javascript' => [
                'vendor/danielgp/common-lib/js/tabber/tabber-management.min.js',
                'vendor/danielgp/common-lib/js/tabber/tabber.min.js',
            ],
            'lang'       => str_replace('_', '-', $this->tCmnSession->get('lang')),
            'title'      => $this->tApp->gettext('i18n_ApplicationName'),
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

    private function setPreliminarySettings($inElmnts)
    {
        $this->appFlags = [
            'DCTD' => $inElmnts['Application']['Default Currencies To Display'],
            'FI'   => $inElmnts['Form Input'],
            'TCAS' => $inElmnts['Table Cell Applied Style'],
            'TCSD' => $inElmnts['Table Cell Style Definitions'],
            'VFR'  => $inElmnts['Values Filter Rules'],
        ];
        $this->initializeSprGlbAndSession();
        $this->handleLocalizationSalariu($inElmnts['Application']);
        echo $this->setHeaderHtml();
    }
}
