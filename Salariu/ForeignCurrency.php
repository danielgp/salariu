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

trait ForeignCurrency
{

    protected $currencyDetails;

    private function getCurrencyExchangeRates($xml, $aryRelevantCurrencies)
    {
        switch ($xml->localName) {
            case 'Cube':
                $this->currencyDetails['CXD'] = strtotime($xml->getAttribute('date'));
                break;
            case 'Rate':
                $multiplier                   = 1;
                if (!is_null($xml->getAttribute('multiplier'))) {
                    $multiplier = $xml->getAttribute('multiplier');
                }
                $this->currencyDetails['CXV'][$xml->getAttribute('currency')] = $xml->readInnerXml() / $multiplier;
                break;
        }
    }

    private function setCurrencyExchangeVariables($aryRelevantCurrencies)
    {
        $this->currencyDetails = [
            'CX'  => $aryRelevantCurrencies,
            'CXD' => strtotime('now'),
        ];
        foreach (array_keys($aryRelevantCurrencies) as $value) {
            $this->currencyDetails['CXV'][$value] = 1;
        }
    }

    protected function setExchangeRateValues($appSettings, $aryRelevantCurrencies)
    {
        $this->setCurrencyExchangeVariables($aryRelevantCurrencies);
        $this->updateCurrencyExchangeRatesFile($appSettings);
        $xml = new \XMLReader();
        if ($xml->open($appSettings['Exchange Rate Local'], 'UTF-8')) {
            while ($xml->read()) {
                if ($xml->nodeType == \XMLReader::ELEMENT) {
                    $this->getCurrencyExchangeRates($xml, array_keys($aryRelevantCurrencies));
                }
            }
            $xml->close();
        }
    }

    private function updateCurrencyExchangeRatesFile($appSettings)
    {
        if ((filemtime($appSettings['Exchange Rate Local']) + 90 * 24 * 60 * 60) < time()) {
            $fCntnt = file_get_contents($appSettings['Exchange Rate Source']);
            if ($fCntnt !== false) {
                file_put_contents($appSettings['Exchange Rate Local'], $fCntnt);
                chmod($appSettings['Exchange Rate Local'], 0666);
            }
        }
    }
}
