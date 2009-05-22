<?php
/**
 * Tracking interface liberay
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 0.1.7
 * @build 20080429
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @license GNU General Public License (GPL)
 */
require_once 'view.common.class.php';
$time_start = null;
$aCurrentPathInfo = null;
$sDecimalPoint = null;
$sThousandsSep = null;
/**
 * Functions used to handle interface
 *
 * @package PGD
 * @author Popiniuc Daniel-Gheorghe <danielpopiniuc@gmail.com>
 * @version 1.0.0
 * @abstract
 * @copyright Popiniuc Daniel-Gheorghe
 * @since 0.1.7
 */
class BasicView extends CommonView
{
	/**
	 * Initialization
	 *
	 * Starts chronometer for measuring processing speed
	 * , cleans all available input
	 * @version 20080423
	 */
	public function __construct()
	{
		global $time_start;
		global $aCurrentPathInfo;
        global $sDecimalPoint;
        global $sThousandsSep;
		$time_start = microtime(true);
		if (isset($_POST)) {
            $_POST = $this->sanitizeBasic($_POST);
        }
		if (isset($_GET)) {
            $_GET = $this->sanitizeBasic($_GET);
        }
		if (isset($_REQUEST)) {
            $_REQUEST = $this->sanitizeBasic($_REQUEST);
        }
		if (isset($_SESSION)) {
            $_SESSION = $this->sanitizeBasic($_SESSION);
        }
        $aCurrentPathInfo = pathinfo($_SERVER['SCRIPT_NAME']);
	    $aLocale = localeconv();
	    $sDecimalPoint = $aLocale['decimal_point'];
	    if ($aLocale['thousands_sep'] == '') {
	        switch($sDecimalPoint) {
	            case '.':
	                $sThousandsSep = ',';
	                break;
	            case ',':
	                $sThousandsSep = '.';
	                break;
	            default:
	                $sThousandsSep = '`';
	                break;
	        }
	    } else {
	        $sThousandsSep = $aLocale['thousands_sep'];
	    }
	}
	/**
	 * cleans elements of given array
	 *
	 * @version 20080423
	 * @param array $internal_array
	 * @return array
	 */
	private function sanitizeBasic($internal_array)
	{
	    $aReturn = null;
	    foreach($internal_array as $key => $value) {
            if (is_array($value)) {
                foreach($value as $key2 => $value2) {
                    if (is_array($value2)) {
                        foreach($value2 as $key3 => $value3) {
                            $aReturn[$key][$key2][$key3] =
                                $this->setStringCleaned($value3);
                        }
                    } else {
                        $aReturn[$key][$key2] =
                            $this->setStringCleaned($value2);
                    }
                }
            } else {
                $aReturn[$key] =
                    $this->setStringCleaned($value);
            }
	    }
	    return $aReturn;
	}
	/**
	 * Generates the page footer
	 *
	 * @version 20080423
	 * @param string $sql_statistics
     * @return string
	 */
	public function setFooter($sql_statistics = null) {
        global $time_start;
        if ($_SERVER['SERVER_NAME'] == 'lugoj2') {
        	include 'piwik_counter.php';
        }
		$sReturn[] = $this->setClearBoth1px();
		$sReturn[] = '<!-- Footer | start -->';
		$sReturn[] = '<div style="text-align: right;">';
		$sReturn[] = $this->setStringIntoTag('T', 'a', array('href' => '#'
		    , 'title' => str_replace('%1',
				round((microtime(true) - $time_start), 5)
				, 'Generating this page took %1 seconds'
			)));
		if ($sql_statistics != null) {
			$sReturn[] = $sql_statistics;
		}
		/**
		 * version_compare()
		 * returns -1 if the first version is lower than the second
		 * , 0 if they are equal, and +1 if the second is lower.
		 */
		if (version_compare(PHP_VERSION, '5.0.0') != -1) {
    		$sReturn[] = $this->setStringIntoTag('M', 'a'
    		    , array('href' => '#'
    		        , 'title' => str_replace('%1',
        				round((memory_get_usage(true) / 1024 / 1024),2)
        				, 'Memory alocated %1 Mb'
    			)));
		}
		$sReturn[] = ' by ' . COPYRIGHT_HOLDER;
		$sReturn[] = '</div>';
		$sReturn[] = '<!-- Footer | end -->';
		$sReturn[] = '</body>';
		$sReturn[] = '</html>';
		return PHP_EOL . implode(PHP_EOL, $sReturn);
    }
    /**
	 * Generates the page footer - light version
	 *
	 * @version 20090119
     * @return string
     */
    public function setFooterLight() {
    	$sReturn[] = '<hr/>';
		$sReturn[] = '</body>';
		$sReturn[] = '</html>';
		return PHP_EOL . implode(PHP_EOL, $sReturn);
    }
	/**
	 * Generates the page header
	 *
	 * @version 20080423
	 * @param string $sPageTitle
	 * @return string
	 */
	public function setHeader($sPageTitle, $aFeatures = null) {
		$sReturn[] = '<!DOCTYPE html PUBLIC '
			. '"-//W3C//DTD XHTML 1.0 Transitional//EN" '
			. '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		$sReturn[] = '<html xmlns="http://www.w3.org/1999/xhtml">';
		$sReturn[] = '<head>';
			$sReturn[] = $this->setStringIntoShortTag('meta'
				, array('http-equiv' => 'Content-Language'
					, 'content' => 'ro-RO'));
			if (strpos($_SERVER["HTTP_ACCEPT"]
				, 'application/xhtml+xml') === false) {
				$sReturn[] = $this->setStringIntoShortTag('meta'
					, array('http-equiv' => 'Content-Type'
						, 'content' => 'text/html; charset=utf-8'));
			} else {
				$sReturn[] = $this->setStringIntoShortTag('meta'
					, array('http-equiv' => 'Content-Type'
						, 'content' => 'application/xhtml+xml; charset=utf-8'));
			}
			$sReturn[] = $this->setStringIntoTag(APP_NAME . ' ' . APP_VERSION
				. ' ' . $sPageTitle, 'title');
			$sReturn[] = '<link rel="icon" type="image/gif" '
				. 'href="/new/images/favicon.gif" />';
			$sReturn[] = $this->setStringIntoShortTag('link'
				, array('href' => 'styles/common.css'
					, 'rel' => 'stylesheet', 'type' => 'text/css'));
			$sReturn[] = $this->setStringIntoShortTag('link'
				, array('href' => 'styles/screen.css'
					, 'rel' => 'stylesheet'
					, 'media' => 'screen', 'type' => 'text/css'));
			if (isset($aFeatures)) {
			    foreach($aFeatures as $key => $value) {
			        switch($key) {
			            case 'css_screen':
			                $css_feature = 'media="screen" ';
			                break;
			            case 'css_print':
			                $css_feature = 'media="print" ';
			                break;
			            case 'css':
			                $css_feature = '';
			                break;
			        }
			        switch($key) {
			            case 'javascript':
			                if (is_array($value)) {
			                    foreach($value as $value2) {
			                        $sReturn[] = '<script src="scripts/'
			                            . $value2 . '"'
				                        . ' type="text/javascript"></script>';
			                    }
			                } else {
			                    $sReturn[] = '<script src="scripts/'
			                        . $value . '"'
				                    . ' type="text/javascript"></script>';
			                }
			                break;
			            case 'css':
			            case 'css_print':
			            case 'css_screen':
			                if (is_array($value)) {
			                    foreach($value as $value2) {
			                        $sReturn[] = '<link href="styles/'
			                            . $value2 . '"'
				                    . ' rel="stylesheet" type="text/css" '
				                    . $css_feature
				                    . '/>';
			                    }
			                } else {
		                        $sReturn[] = '<link href="styles/'
			                        . $value . '"'
				                    . ' rel="stylesheet" type="text/css" '
				                    . $css_feature
				                    . '/>';
			                }
			                break;
			            case 'css_ie':
	                        $sReturn[] = '<!--[if IE]>' . PHP_EOL
	                        	. '<link href="styles/' . $value . '"'
		                    	. ' rel="stylesheet" type="text/css" '
				                . ' media="screen" />' . PHP_EOL
			                    . '<![endif]-->';
			            	break;
			            case 'css_ie7':
	                        $sReturn[] = '<!--[if lt IE 7]>' . PHP_EOL
	                        	. '<link href="styles/' . $value . '"'
		                    	. ' rel="stylesheet" type="text/css" '
				                . ' media="screen" />' . PHP_EOL
			                    . '<![endif]-->';
			            	break;
			        }
			    }
			}
		$sReturn[] = '</head>';
		$sReturn[] = '<body>';
		return implode(PHP_EOL, $sReturn) . PHP_EOL;
	}
	/**
	 * Generates the page header - light version
	 *
	 * @version 20090119
	 * @return string
	 */
	public function setHeaderLight($sPageTitle) {
		$sReturn[] = '<html>';
		$sReturn[] = '<head>';
		$sReturn[] = $this->setStringIntoTag($sPageTitle, 'title');
		$sReturn[] = $this->setStringIntoShortTag('link'
			, array('href' => 'styles/common.css'
				, 'rel' => 'stylesheet'
				, 'type' => 'text/css'));
		$sReturn[] = $this->setStringIntoShortTag('link'
			, array('href' => 'styles/tight.css'
				, 'rel' => 'stylesheet'
				, 'type' => 'text/css'));
		$sReturn[] = '</head>';
		$sReturn[] = '<body>';
		return implode(PHP_EOL, $sReturn) . PHP_EOL;
	}
	/**
	 * Displays regular rows for Dashboard
	 *
	 * @version 20080423
	 * @param string $row_class
	 * @param string $first_column
	 * @param array $row
	 * @param boolean $optimize
	 * @return string
	 */
	public function setRegularRows($sRowClass, $first_column, $row)
	{
        global $sDecimalPoint;
        global $sThousandsSep;
		$sReturn = '<tr>';
			$sReturn .= $this->setSingleCell($sRowClass
			    , $first_column
			    , 'text-align: left;');
			$sReturn .= $this->setSingleCell($sRowClass
					, $this->setDividedResult($row[1], 1
					    , array(0, $sDecimalPoint
					        , $sThousandsSep)
				    ));
			$sReturn .= $this->setSingleCell($sRowClass
					, $this->setDividedResult($row[2], 1
					    , array(0, $sDecimalPoint
					        , $sThousandsSep)
				    ));
            $v = $this->setDividedResult($row[12], 1
                , array(0, $sDecimalPoint
                    , $sThousandsSep));
		    if ($row[12] != 0) {
		        $info = pathinfo($_SERVER['SCRIPT_NAME']);
                if (($info['basename'] == 'app.dashboard.php')
                	 && (isset($row[7]))) {
                    $arguments = '';
                    switch($_GET['feature']) {
                        case 'basic':
                            $arguments = 'ServerId='
                                . $row[7];
                            break;
                        case 'dashboard':
                            $arguments = 'ServerId='
                                . $row[7]
                                . '&amp;DbId='
                                . $row[9];
                            break;
                        case 'detailed':
                            $arguments = 'ServerId='
                                . $row[7]
                                . '&amp;DbId='
                                . $row[10]
                                . '&amp;TableId='
                                . $row[11];
                            break;
                    }
                    $vfx = '<a href="app.fix.overhead.php?'
                        . $arguments
                        . '" '
                        . 'title="Optimize">'
                        . $this->setIcons('support')
                        . '</a>&nbsp;';
                } else {
                    $vfx = '';
                }
		        $v = '<span class="warning">' . $vfx . $v . '</span>';
		    }
			$sReturn .= $this->setSingleCell($sRowClass, $v);
			$sReturn .= $this->setSingleCell($sRowClass
					, $this->setDividedResult($row[3], 1
					    , array(0, $sDecimalPoint
					        , $sThousandsSep)
				    ));
			$sReturn .= $this->setSingleCell($sRowClass
					, ($this->setDividedResult(($row[3]*100), $row[2]
					    , array(2, $sDecimalPoint
					        , $sThousandsSep))
					    . '%'));
			$sReturn .= $this->setSingleCell($sRowClass
					, $this->setDividedResult(($row[2]+$row[12]+$row[3])
					    , 1, array(0, $sDecimalPoint
					        , $sThousandsSep)));
			$sReturn .= $this->setSingleCell($sRowClass
					, $this->setDividedResult($row[2], $row[1]
					    , array(2, $sDecimalPoint
					        , $sThousandsSep)));
			$sReturn .= $this->setSingleCell($sRowClass
					, $this->setDividedResult($row[3], $row[1]
					    , array(2, $sDecimalPoint
					        , $sThousandsSep)));
			$sReturn .= $this->setSingleCell($sRowClass
					, $this->setDividedResult(($row[2]+$row[12]+$row[3])
					    , $row[1], array(2, $sDecimalPoint
					        , $sThousandsSep)));
		$sReturn .= '</tr>';
		return $sReturn;
	}
    /**
     * cleans a given strigs of unwanted/dangerous characters
     *
	 * @version 20080423
     * @param string $sInputString
     * @return string
     */
    private function setStringCleaned($sInputString)
    {
        return str_replace(
		    array('"', "'")
		    , array('`', "`")
		    , strip_tags($sInputString)
		);
    }
}