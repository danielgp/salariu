<?php
/**
 * Multilanguage interface libray
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 0.1.7
 * @build 20080618
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @license GNU General Public License (GPL)
 */
require_once 'view.common.class.php';
/**
 * Functions used to multilanguage interface
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 1.0.0
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @since 0.1.7
 */
class MultiLanguage extends CommonView {
	public function getMonthName($countryA3, $monthNo = null) {
		switch($countryA3) {
			case 'enu':
				$month_names = array(
					1 => 'January'
					, 2 => 'February'
					, 3 => 'March'
					, 4 => 'April'
					, 5 => 'May'
					, 6 => 'June'
					, 7 => 'July'
					, 8 => 'August'
					, 9 => 'September'
					, 10 => 'October'
					, 11 => 'November'
					, 12 => 'December'
				);
				if ($monthNo == null) {
					$sReturn = $month_names;
				} else {
					$sReturn = $month_names[$monthNo];
				}
				break;
			case 'rom':
				$month_names = array(
					1 => 'ianuarie'
					, 2 => 'februarie'
					, 3 => 'martie'
					, 4 => 'aprilie'
					, 5 => 'mai'
					, 6 => 'iunie'
					, 7 => 'iulie'
					, 8 => 'august'
					, 9 => 'septembrie'
					, 10 => 'octombrie'
					, 11 => 'noiembrie'
					, 12 => 'decembrie'
				);
				if ($monthNo == null) {
					$sReturn = $month_names;
				} else {
					$sReturn = $month_names[$monthNo];
				}
				break;
			default:
				$sReturn = null;
				break;
		}
		return $sReturn;
	}
	public function getWeekDayName($countryA3, $weekdayNo = null) {
		switch($countryA3) {
			case 'enu':
				$weekday_names = array(
					'Sunday',
					'Monday',
					'Tuesday',
					'Wednesday',
					'Thursday',
					'Friday',
					'Saturday'
				);
				if ($weekdayNo == null) {
					$sReturn = $weekday_names;
				} else {
					$sReturn = $weekday_names[$weekdayNo];
				}
				break;
			case 'rom':
				$weekday_names = array(
					'duminica',
					'luni',
					'marti',
					'miercuri',
					'joi',
					'vineri',
					'sambata'
				);
				if ($weekdayNo == null) {
					$sReturn = $weekday_names;
				} else {
					$sReturn = $weekday_names[$weekdayNo];
				}
				break;
			default:
				$sReturn = null;
				break;
		}
		return $sReturn;
	}
	public function setFlags($countryA3, $setDefault = false, $inclLink = true) {
		switch($countryA3) {
			case 'deu':
                $sReturn = array(
                    'small_flag' => 'flag_germany_small.gif',
                    'normal_flag' => 'flag_germany.gif',
                    'alternate_text' => 'Deutsch');
                break;
			case 'enu':
                $sReturn = array(
                    'small_flag' => 'flag_usa_small.gif',
                    'normal_flag' => 'flag_usa.gif',
                    'alternate_text' => 'English');
                break;
			case 'ita':
                $sReturn = array(
                    'small_flag' => 'flag_italy_small.gif',
                    'normal_flag' => 'flag_italy.gif',
                    'alternate_text' => 'Italiano');
                break;
			case 'rom':
                $sReturn = array(
                    'small_flag' => 'flag_romania_small.gif',
                    'normal_flag' => 'flag_romania.gif',
                    'alternate_text' => 'Rom&acirc;na');
                break;
			default:
                die("<div class='fatal_error'>"
                    . str_replace(array('%1', '%2')
                    , array($countryA3, __FUNCTION__)
                    , MMESAGE_error_function_parameters)
                    . "</h1>");
                break;
		}
		$sizes = array(
            "normal" => "width='35' height='21'"
            , "small" => "width='24' height='15'");
		if ($setDefault) {
				if (isset($_SESSION['lng'])) {
                    if ($_SESSION['lng'] == $countryA3) {
                        $flag_name = $sReturn['normal_flag'];
                        $size = $sizes['normal'];
                    } else {
                        $flag_name = $sReturn['small_flag'];
                        $size = $sizes['small'];
                    }
                } else {
                    $flag_name = $sReturn['normal_flag'];
                    $size = $sizes['normal'];
				}
        } else {
				if (isset($_SESSION['lng'])) {
                    if ($_SESSION['lng'] == $countryA3) {
                        $flag_name = $sReturn['normal_flag'];
                        $size = $sizes['normal'];
                    } else {
                        $flag_name = $sReturn['small_flag'];
                        $size = $sizes['small'];
                    }
                } else {
                    $flag_name = $sReturn['small_flag'];
                    $size = $sizes['small'];
				}
		}
		$image_loaded = "<img src='" . IMAGES_FOLDER . '/flag/' . $flag_name
            . "' " . $size . " alt='" . $sReturn['alternate_text']
            . "' title='" . $sReturn['alternate_text'] . "'/>";
		if ($inclLink) {
				if (isset($_GET['lng'])) {
                    $_GET['lng'] = $countryA3;
                }
				if (isset($_POST['lng'])) {
                    $_POST['lng'] = $countryA3;
                }
				if (isset($_REQUEST['lng'])) {
                    $_POST['lng'] = $countryA3;
                }
				$array2transmit = array_diff(
                    array_merge(@$_GET, @$_POST)
                    , array('username' => @$_REQUEST['username']
                        , 'password' => @$_REQUEST['password']));
				$string_to_return = "<a href='?"
                    . $this->setArray2String4Url('&amp;', $array2transmit);
				if (!isset($_REQUEST['lng'])) {
					if (count($array2transmit) >= 1) {
                        $string_to_return .= "&amp;";
                    }
					$string_to_return .= "lng=" . $countryA3;
				}
				$string_to_return .= "'>" . $image_loaded . "</a>";
			} else {
				$string_to_return = $image_loaded;
		}
		return "&nbsp;&nbsp;".$string_to_return;
	}
}
?>