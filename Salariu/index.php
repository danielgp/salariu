<?php
require_once 'config/salariu.config.inc.php';
require_once 'config/default.values.inc.php';
require_once 'language/rom.inc.php';
require_once 'lib/algorithm.inc.php';
/**
 * Main page
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 1.0
 * @build 20090519
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @license GNU General Public License (GPL)
 */
/**
 * Main Functions used to handle GUI
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 1.0.0
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @since 1.0.0
 */
class Salariu extends RomanianSalary {
	private $exchangeRateDate;
	private $exchangeRateEur;
	private $exchangeRateUsd;
	private function getExchangeRates() {
		$this->exchangeRateDate = date();
		$this->exchangeRateEur = 0;
		$this->exchangeRateUsd = 0;
		$xml = new XMLReader();
		$x = 'exchange_rates.xml';
		if ($_SERVER['SERVER_NAME'] == 'localhost') {
			$f = file_get_contents(EXCHANGE_RATES_SOURCE);
			file_put_contents($x, $f);
		}
		if ($xml->open($x, 'UTF-8')) {
			while ($xml->read()) {
				if ($xml->nodeType == XMLReader::ELEMENT) {
					switch($xml->localName) {
						case 'Cube':
							$v = $xml->getAttribute('date');
							$this->exchangeRateDate = mktime(0, 0, 0
								, substr($v, 5, 2)
								, substr($v, -2)
								, substr($v, 0, 4));
							break;
						case 'Rate':
							switch ($xml->getAttribute('currency')) {
								case 'EUR':
									$this->exchangeRateEur = $xml->readInnerXml();
									break;
								case 'USD':
									$this->exchangeRateUsd = $xml->readInnerXml();
									break;
							}
							break;
					}
				}
			}
			$xml->close();
		} else {
			echo '<div style="background-color: red; color: white;">';
			print_r(error_get_last());
			echo '</div>';
		}
	}
    private function setFormInput() {
		global $month_names;
		for ($counter = date('Y'); $counter >= 2001 ; $counter--) {
			for ($counter2 = 12; $counter2 >= 1; $counter2--) {
				if (($counter == date('Y')) && ($counter2 > date('m'))) {
						# se limiteaza pana la luna curenta
					} else {
						$temp[mktime(0, 0, 0, $counter2, 1, $counter)] =
							strftime('%Y_%m'
								, mktime(0, 0, 0, $counter2, 1, $counter))
							. ' (' . $month_names[$counter2] . ')';
				}
			}
		}
		$sReturn[] = $this->setFormRow('Luna de calcul'
			, $this->setArray2Select($temp, @$_REQUEST['ym'], 'ym'
				, array('size' => 1)), 1);
		unset($temp);
		$sReturn[] = $this->setFormRow('Salariu negociat'
			, $this->setStringIntoShortTag('input'
				, array('name' => 'sn', 'value' => @$_REQUEST['sn']
					, 'size' => 10)) . ' RON', 1);
		$sReturn[] = $this->setFormRow('Spor cumulat'
			, $this->setStringIntoShortTag('input'
				, array('name' => 'sc', 'value' => @$_REQUEST['sc']
					, 'size' => 2)) . ' %', 1);
		$sReturn[] = $this->setFormRow('Prima bruta'
			, $this->setStringIntoShortTag('input'
				, array('name' => 'pb', 'value' => @$_REQUEST['pb']
					, 'size' => 10)) . ' RON', 1);
		$sReturn[] = $this->setFormRow('Prima neta'
			, $this->setStringIntoShortTag('input'
				, array('name' => 'pn', 'value' => @$_REQUEST['pn']
					, 'size' => 10)) . ' RON', 1);
		$sReturn[] = $this->setFormRow('Ore suplimentare normale (175%)'
			, $this->setStringIntoShortTag('input'
				, array('name' => 'os175', 'value' => @$_REQUEST['os175']
					, 'size' => 2)), 1);
		$sReturn[] = $this->setFormRow('Ore suplimentare speciale (200%)'
			, $this->setStringIntoShortTag('input'
				, array('name' => 'os200', 'value' => @$_REQUEST['os200']
					, 'size' => 2)), 1);
		for($counter = 0; $counter <= 4; $counter++) {
			$temp[] = $counter;
		}
		$sReturn[] = $this->setFormRow('Persoane aflate in intretinere'
			, $this->setArray2Select($temp, @$_REQUEST['pi'], 'pi'
				, array('size' => 1)), 1);
		unset($temp);
		$temp = array('Da', 'Nu');
		$sReturn[] = $this->setFormRow('Pastele catolic e liber?'
			, $this->setArray2Select($temp, @$_REQUEST['pc'], 'pc'
				, array('size' => 1)), 1);
		$sReturn[] = $this->setFormRow('Zile lucrate fara bonuri de alim.'
			, $this->setStringIntoShortTag('input'
				, array('name' => 'zfb', 'value' => @$_REQUEST['zfb']
					, 'size' => 2)), 1);
		$sReturn[] = $this->setStringIntoTag(
			$this->setStringIntoTag('Toate campurile trebuie '
					. 'sa aiba o valoare in momentul transmiterii datelor!'
					. $this->setStringIntoShortTag('input'
						, array('type' => 'hidden', 'name' => 'action'
							, 'value' => $_SERVER['SERVER_NAME']))
				, 'td', array('colspan' => 2, 'class' => 'only_screen'))
			, 'tr');
		$sReturn[] = $this->setFormRow(
			$this->setStringIntoShortTag('input'
				, array('type' => 'reset', 'id' => 'reset'
					, 'value' => 'Revino la valorile initiale'))
			, $this->setStringIntoShortTag('input'
				, array('type' => 'submit', 'id' => 'submit'
					, 'value' => 'Da-mi rezultatele')), 1);
		return $this->setStringIntoTag('Date initiale', 'h2')
			. $this->setStringIntoTag(
				$this->setStringIntoTag(
					$this->setStringIntoTag(implode(PHP_EOL, $sReturn)
						, 'table', array('border' => 0, 'cellpadding' => 0
							, 'cellspacing' => 0))
					, 'form', array('method' => 'get'
						, 'action' => $_SERVER['SCRIPT_NAME']))
			, 'div');
    }
    private function setFormOutput() {
		$overtime = $this->getOvertimes();
		$brut = ($_REQUEST['sn'] * (1 + $_REQUEST['sc']/100)
    		+ $_REQUEST['pb'] + $_REQUEST['pn']
    		+ $overtime['os175'] + $overtime['os200']) * pow(10, 4);
		$sReturn[] = $this->setFormRow('Suma o.s. 175%', 'o175'
			, ($overtime['os175'] * pow(10, 4)));
		$sReturn[] = $this->setFormRow('Suma o.s. 200%', 'o200'
			, ($overtime['os200'] * pow(10, 4)));
		$sReturn[] = $this->setFormRow('Salariu brut', $brut);
    	$amount = $this->getValues($brut);
		$sReturn[] = $this->setFormRow('CAS', $amount['cas']);
		$sReturn[] = $this->setFormRow('Sanatate', $amount['sanatate']);
		$sReturn[] = $this->setFormRow('Somaj', $amount['somaj']);
		$sReturn[] = $this->setFormRow('Impozit', $amount['impozit']);
		$net = ($brut - $amount['cas'] - $amount['somaj'] - $amount['sanatate']
				- $amount['impozit'] - $_REQUEST['pn']);
		$sReturn[] = $this->setFormRow('&nbsp;', '&nbsp;', 1);
		$sReturn[] = $this->setFormRow('Salariu net', $net);
		$sReturn[] = $this->setFormRow('Zile lucratoare', $amount['zile']
			, 'value');
		$sReturn[] = $this->setFormRow('Bonuri alimente', $amount['ba']);
		$sReturn[] = $this->setFormRow('TOTAL', ($net + $amount['ba']));
		return $this->setStringIntoTag('Rezultate', 'h2')
			. $this->setStringIntoTag(
				$this->setStringIntoTag(implode(PHP_EOL, $sReturn)
					, 'table', array('border' => 0, 'cellpadding' => 0
						, 'cellspacing' => 0))
			, 'div');
    }
    private function setFormRow($text, $value, $type = 'amount') {
    	$a = '';
    	switch($type) {
    		case 'amount':
    			$value = $value / pow(10, 4);
    			if ($this->exchangeRateEur == 0) {
	    			$value = $this->setStringIntoTag(
    					number_format($value, 2
							, DECIMAL_SEPARATOR, THOUSAND_SEPARATOR) . ' RON'
						, 'td', array('class' => 'labelS'));
    			} else {
	    			$value = $this->setStringIntoTag(
    					number_format($value, 2
							, DECIMAL_SEPARATOR, THOUSAND_SEPARATOR) . ' RON'
						, 'td', array('class' => 'labelS'))
					. $this->setStringIntoTag(
    					number_format($value / $this->exchangeRateEur, 2
							, DECIMAL_SEPARATOR, THOUSAND_SEPARATOR) . ' EUR'
						, 'td', array('class' => 'labelS'))
					. $this->setStringIntoTag(
    					number_format($value / $this->exchangeRateUsd, 2
							, DECIMAL_SEPARATOR, THOUSAND_SEPARATOR) . ' USD'
						, 'td', array('class' => 'labelS'));
    			}
				break;
    		case 'value':
    			$value = $this->setStringIntoTag($value . $a
					, 'td', array('class' => 'labelS'));
    			break;
    		default:
    			$value = $this->setStringIntoTag($value, 'td');
    			break;
    	}
    	if ($text != '&nbsp;') {
    		$text .= ':';
    	}
    	return $this->setStringIntoTag($this->setStringIntoTag($text, 'td')
    		. $value, 'tr');
    }
	public function setInterface() {
        $sReturn[] = $this->setHeader('', array('css' => 'salariu.css'
        	, 'css_print' => 'print.css'));
        $sReturn[] = $this->setStringIntoTag(APP_NAME, 'h1');
        $sReturn[] = $this->setFormInput();
        if (isset($_REQUEST['action'])) {
        	$this->getExchangeRates();
        	$sReturn[] = $this->setFormOutput();
    		if ($this->exchangeRateEur != 0) {
	        	$sReturn[] = $this->setStringIntoTag(
	        		'Sursa ratelor de schimb valutar pentru EUR ('.
	        		$this->exchangeRateEur
	        		. ') si USD (' . $this->exchangeRateUsd . ') este BNR '
	        		. ' din data ' . date('d.m.Y', $this->exchangeRateDate)
	        			, 'div');
    		}
        }
		$sReturn[] = $this->setStringIntoTag(
			$this->setStringIntoTag('Autorul nu isi asuma nici '
			. 'un fel de raspundere privitoare atat la datele introduse '
			. 'cat nici la rezultatele calculate inclusiv implicatiile '
			. 'acestora (oriunde si oricare ar putea fi acestea)!', 'div'
			, array('style' => 'float: none; clear: both;'
				. 'color: blue; text-align: center; width: 60%;'))
			, 'center');
       	$sReturn[] = $this->setStringIntoTag(
           	'v. ' . APP_VERSION . ' (' . APP_BUILD . ')', 'div'
           	, array('style' => 'float: right; margin-top: 0px;'));
        if ($_SERVER['SERVER_NAME'] == 'salariu.sourceforge.net') {
        	$sReturn[] = <<<EOT
<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://apps.sourceforge.net/piwik/salariu/" : "http://apps.sourceforge.net/piwik/salariu/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script><script type="text/javascript">
piwik_action_name = '';
piwik_idsite = 1;
piwik_url = pkBaseURL + "piwik.php";
piwik_log(piwik_action_name, piwik_idsite, piwik_url);
</script>
<object><noscript><p><img src="http://apps.sourceforge.net/piwik/salariu/piwik.php?idsite=1" alt="piwik"/></p></noscript></object>
<!-- End Piwik Tag -->
EOT;
        }
        $sReturn[] = $this->setFooter();
        echo implode(PHP_EOL, $sReturn);
	}
	private function setResults() {
		$sReturn[] = '';
		return implode(PHP_EOL, $sReturn);
	}
}
$app = new Salariu();
echo $app->setInterface();
?>