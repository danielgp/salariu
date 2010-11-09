<?php
require_once 'config/salariu.config.inc.php';
require_once 'language/rom.inc.php';
require_once 'language/rom.salariu.inc.php';
define('APP_NAME', $msg_title);
require_once 'config/default.values.inc.php';
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
	private $exchangeRates;
	private $exchangeRatesValue;
	private function getExchangeRates() {
		$this->exchangeRateDate = date();
		$this->exchangeRates = array('RON' => 0, 'EUR' => 2, 'HUF' => 0
			, 'GBP' => 2, 'CHF' => 2, 'USD' => 2);
		foreach($this->exchangeRates as $key => $value) {
			$this->exchangeRatesValue[$key] = 1;
		}
		$xml = new XMLReader();
		$x = 'nbrfxrates.xml';
		/*if (($_SERVER['SERVER_NAME'] == 'localhost')
			|| ($_SERVER['SERVER_NAME'] == '127.0.0.1')) {
			$f = file_get_contents(EXCHANGE_RATES_SOURCE);
			file_put_contents($x, $f);
		}*/
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
							if (in_array($xml->getAttribute('currency')
								, array_keys($this->exchangeRates))) {
								$this->exchangeRatesValue[
									$xml->getAttribute('currency')] = 
										$xml->readInnerXml();
								if (!is_null($xml->getAttribute('multiplier'))){
									$this->exchangeRatesValue[
										$xml->getAttribute('currency')] = 
									$this->exchangeRatesValue[
										$xml->getAttribute('currency')] /
										$xml->getAttribute('multiplier'); 
								}
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
		global $msg_category_initial;
		global $msg_choice;
		global $msg_initial_label;
		global $msg_initial_requirement;
		global $msg_initial_button;
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
		$sReturn[] = $this->setFormRow($msg_initial_label['base_month']
			, $this->setArray2Select($temp, @$_REQUEST['ym'], 'ym'
				, array('size' => 1)), 1);
		unset($temp);
		$sReturn[] = $this->setFormRow($msg_initial_label['negociated_salary']
			, $this->setStringIntoShortTag('input'
				, array('name' => 'sn', 'value' => @$_REQUEST['sn']
					, 'size' => 10)) . ' RON', 1);
		$sReturn[] = $this->setFormRow(
			$msg_initial_label['cumulated_added_value']
			, $this->setStringIntoShortTag('input'
				, array('name' => 'sc', 'value' => @$_REQUEST['sc']
					, 'size' => 2)) . ' %', 1);
		$sReturn[] = $this->setFormRow($msg_initial_label['bonus_brutto']
			, $this->setStringIntoShortTag('input'
				, array('name' => 'pb', 'value' => @$_REQUEST['pb']
					, 'size' => 10)) . ' RON', 1);
		$sReturn[] = $this->setFormRow($msg_initial_label['bonus_netto']
			, $this->setStringIntoShortTag('input'
				, array('name' => 'pn', 'value' => @$_REQUEST['pn']
					, 'size' => 10)) . ' RON', 1);
		$sReturn[] = $this->setFormRow(
			str_replace(array('%1', '%2')
				, array($msg_choice['overtime_hours']['choice_1'], '175%')
				, $msg_initial_label['overtime_hours'])
			, $this->setStringIntoShortTag('input'
				, array('name' => 'os175', 'value' => @$_REQUEST['os175']
					, 'size' => 2)), 1);
		$sReturn[] = $this->setFormRow(
			str_replace(array('%1', '%2')
				, array($msg_choice['overtime_hours']['choice_2'], '200%')
				, $msg_initial_label['overtime_hours'])
			, $this->setStringIntoShortTag('input'
				, array('name' => 'os200', 'value' => @$_REQUEST['os200']
					, 'size' => 2)), 1);
		for($counter = 0; $counter <= 4; $counter++) {
			$temp[] = $counter;
		}
		$sReturn[] = $this->setFormRow($msg_initial_label['persons_supported']
			, $this->setArray2Select($temp, @$_REQUEST['pi'], 'pi'
				, array('size' => 1)), 1);
		unset($temp);
		$choices = $msg_initial_label['catholic_ester_free_choice'];
		$sReturn[] = $this->setFormRow($msg_initial_label['catholic_ester_free']
			, $this->setArray2Select($choices, @$_REQUEST['pc'], 'pc'
				, array('size' => 1)), 1);
		unset($choices);
		$sReturn[] = $this->setFormRow(
			$msg_initial_label['working_days_wo_food_bonuses']
			, $this->setStringIntoShortTag('input'
				, array('name' => 'zfb', 'value' => @$_REQUEST['zfb']
					, 'size' => 2)), 1);
		$sReturn[] = $this->setStringIntoTag(
			$this->setStringIntoTag($msg_initial_requirement
					. $this->setStringIntoShortTag('input'
						, array('type' => 'hidden', 'name' => 'action'
							, 'value' => $_SERVER['SERVER_NAME']))
				, 'td', array('colspan' => 2, 'style' => 'color: red;'))
			, 'tr');
		if (isset($_GET['ym'])) {
			$reset_btn = '';
			$submit_btn_txt = $msg_initial_button['results']['final'];
		} else {
			$reset_btn = $this->setStringIntoShortTag('input'
				, array('type' => 'reset', 'id' => 'reset'
					, 'value' => $msg_initial_button['reset']));
			$submit_btn_txt = $msg_initial_button['results']['initial'];
		}
		$sReturn[] = $this->setFormRow($reset_btn
			, $this->setStringIntoShortTag('input'
				, array('type' => 'submit', 'id' => 'submit'
					, 'value' => $submit_btn_txt)), 1);
		return $this->setStringIntoTag(
				$this->setStringIntoTag($msg_category_initial, 'legend')
				. $this->setStringIntoTag(
					$this->setStringIntoTag(implode(PHP_EOL, $sReturn)
						, 'table', array('border' => 0, 'cellpadding' => 0
							, 'cellspacing' => 0))
					, 'form', array('method' => 'get'
						, 'action' => $_SERVER['SCRIPT_NAME']))
				, 'fieldset', array('style' => 'float: left;'));
    }
    private function setFormOutput() {
    	global $month_names;
    	global $msg_category_final;
		global $msg_choice;
    	global $msg_final_label;
		global $msg_initial_label;
		$overtime = $this->getOvertimes();
		$brut = ($_REQUEST['sn'] * (1 + $_REQUEST['sc']/100)
    		+ $_REQUEST['pb'] + $overtime['os175'] + $overtime['os200']) 
    		* pow(10, 4);
		$sReturn[] = $this->setFormRow(
			str_replace('%1', date('d.m.Y', $this->exchangeRateDate)
				, $msg_final_label['exchange_rate']), 10000);
		$sReturn[] = $this->setFormRow($msg_initial_label['negociated_salary']
				, $_REQUEST['sn']*10000);
		$sReturn[] = $this->setFormRow(
			$msg_initial_label['cumulated_added_value']
				, $_REQUEST['sn']*$_REQUEST['sc']*100);
		$sReturn[] = $this->setFormRow($msg_initial_label['bonus_brutto']
			, $_REQUEST['pb']*10000);
		$sReturn[] = $this->setFormRow(
			str_replace(array('%1', '%2')
				, array($msg_choice['overtime_hours']['choice_1'], '175%')
				, $msg_final_label['amount_overtime'])
			, ($overtime['os175'] * pow(10, 4)));
		$sReturn[] = $this->setFormRow(
			str_replace(array('%1', '%2')
				, array($msg_choice['overtime_hours']['choice_2'], '200%')
				, $msg_final_label['amount_overtime'])
			, ($overtime['os200'] * pow(10, 4)));
		$sReturn[] = $this->setFormRow($msg_final_label['salary_brutto']
			, $brut);
    	$amount = $this->getValues($brut);
		$sReturn[] = $this->setFormRow($msg_final_label['pension']
			, $amount['cas']);
		$sReturn[] = $this->setFormRow(
			$msg_final_label['state_health_insurance'], $amount['sanatate']);
		$sReturn[] = $this->setFormRow($msg_final_label['unemployment']
			, $amount['somaj']);
		$sReturn[] = $this->setFormRow($msg_final_label['tax_payable']
			, $amount['impozit']);
		$net = $brut - $amount['cas'] - $amount['somaj'] - $amount['sanatate']
				- $amount['impozit'] + $_REQUEST['pn']*10000;
		$sReturn[] = $this->setFormRow($msg_initial_label['bonus_netto']
			, $_REQUEST['pn']*10000);
		$sReturn[] = $this->setFormRow($msg_final_label['salary_netto'], $net);
		$sReturn[] = $this->setFormRow(
			str_replace(array('%1', '%2')
				, array($month_names[date('m', $_GET['ym'])]
					, date('Y', $_GET['ym']))
				, $msg_final_label['working_days'])
			, $amount['zile'], 'value');
		$sReturn[] = $this->setFormRow(
			str_replace('%1', $msg_choice['food_bonuses']['choice_qty']
				, $msg_final_label['food_bonuses'])
			, $amount['zile'] - $_REQUEST['zfb'], 'value');
		$sReturn[] = $this->setFormRow(
			str_replace('%1', $msg_choice['food_bonuses']['choice_amt']
				, $msg_final_label['food_bonuses'])
			, $amount['ba']);
		$sReturn[] = $this->setFormRow($msg_final_label['total']
			, ($net + $amount['ba']));
		return $this->setStringIntoTag(
				$this->setStringIntoTag($msg_category_final, 'legend')
				. $this->setStringIntoTag(implode(PHP_EOL, $sReturn)
					, 'table', array('border' => 0, 'cellpadding' => 0
						, 'cellspacing' => 0))
				, 'fieldset', array('style' => 'float: left;'));
    }
    private function setFormRow($text, $value, $type = 'amount') {
    	global $msg_final_label;
		global $msg_initial_label;
    	$a = '';
    	$defaultCellStyle = array('class' => 'labelS');
    	switch($text) {
			case $msg_initial_label['negociated_salary']:
    		case $msg_final_label['salary_brutto']:
    		case $msg_final_label['salary_netto']:
	    		$defaultCellStyle = array_merge($defaultCellStyle
	    			, array('style' => 'color: #000000; font-weight: bold;'));
    			break;
    		case $msg_final_label['pension']:
    		case $msg_final_label['state_health_insurance']:
    		case $msg_final_label['unemployment']:
    		case $msg_final_label['tax_payable']:
	    		$defaultCellStyle = array_merge($defaultCellStyle
	    			, array('style' => 'color: #ff9900; '));
    			break;
    		case $msg_final_label['total']:
	    		$defaultCellStyle = array_merge($defaultCellStyle
	    			, array('style' => 'font-weight: bold; color: #009933; '
	    				. 'font-size: larger;'));
    			break;
    	}
		if ((is_numeric($value)) && ($value == 0)) {
			if (isset($defaultCellStyle['style'])) {
				$defaultCellStyle['style'] = 'color: #dcdcdc;';
			} else {
	    		$defaultCellStyle = array_merge($defaultCellStyle
	    			, array('style' => 'color: #dcdcdc;'));
			}
		}
    	switch($type) {
    		case 'amount':
    			$value = $value / pow(10, 4);
	    		$defaultCellStyle2['style'] = $defaultCellStyle['style'] 
	    			. 'text-align: right;';
				foreach($this->exchangeRates as $key2 => $value2) {
					$cellValue[] = $this->setStringIntoTag(
						number_format(
							$value / $this->exchangeRatesValue[$key2], $value2
							, DECIMAL_SEPARATOR, THOUSAND_SEPARATOR) 
						. '&nbsp;' . $key2
						, 'td', $defaultCellStyle2);
				}
				$value2show = implode('', $cellValue);
				break;
    		case 'value':
    			$value2show = $this->setStringIntoTag($value . $a
					, 'td', $defaultCellStyle);
    			break;
    		default:
    			$value2show = $this->setStringIntoTag($value, 'td');
    			break;
    	}
    	if ($text != '&nbsp;') {
    		$text .= ':';
    	}
    	return $this->setStringIntoTag(
    		$this->setStringIntoTag($text, 'td', $defaultCellStyle)
    		. $value2show, 'tr');
    }
	public function setInterface() {
		global $msg_disclaimer;
        $sReturn[] = $this->setHeader('');
        $sReturn[] = $this->setStringIntoTag(APP_NAME, 'h1');
        $sReturn[] = $this->setFormInput();
        if (isset($_REQUEST['action'])) {
        	$this->getExchangeRates();
        	$sReturn[] = $this->setFormOutput();
        }
		$sReturn[] = $this->setStringIntoTag(
			$this->setStringIntoTag($msg_disclaimer, 'div'
			, array('style' => 'float: none; clear: both;'
				. 'border: solid;border-color: grey;background-color: yellow;'
				. 'color: red; text-align: center; width: 50%;'))
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
piwik_action_name = '';piwik_idsite = 1;piwik_url = pkBaseURL + "piwik.php";piwik_log(piwik_action_name, piwik_idsite, piwik_url);
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