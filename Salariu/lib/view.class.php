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
require_once 'mui.class.php';

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
class BasicView extends MultiLanguage
{

    private $time_start   = 0;
    private $thisFile     = null;
    public $sDecimalPoint = null;
    public $sThousandsSep = null;

    /**
     * Initialization
     *
     * Starts chronometer for measuring processing speed
     * , cleans all available input
     * @version 20080423
     */
    public function __construct()
    {
        $sanitize_needed  = true;
        $this->time_start = microtime(true);
        if ((isset($_REQUEST['action'])) || ($_REQUEST['action'] == 'save_FrontpageId')) {
            $sanitize_needed = false;
        }
        if ($sanitize_needed) {
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
        }
        $this->thisFile      = pathinfo($_SERVER['SCRIPT_NAME']);
        $aLocale             = localeconv();
        $this->sDecimalPoint = $aLocale['decimal_point'];
        if ($aLocale['thousands_sep'] == '') {
            switch ($this->sDecimalPoint) {
                case '.':
                    $this->sThousandsSep = ',';
                    break;
                case ',':
                    $this->sThousandsSep = '.';
                    break;
                default:
                    $this->sThousandsSep = '`';
                    break;
            }
        } else {
            $this->sThousandsSep = $aLocale['thousands_sep'];
        }
    }

    /**
     * Get the full information about OS running PHP
     *
     * @version 20090720
     * @return string
     */
    private function getOSinfo()
    {
        ob_start();
        phpinfo(INFO_GENERAL);
        $phpinfo = array('phpinfo' => array());
        if (preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)'
                . '|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*'
                . '</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?'
                . ': class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s'
                , ob_get_clean(), $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (strlen($match[1]))
                    $phpinfo[$match[1]]                            = array();
                elseif (isset($match[3]))
                    $phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
                else
                    $phpinfo[end(array_keys($phpinfo))][]          = $match[2];
            }
        }
        return $phpinfo['phpinfo']['System'];
    }

    /**
     * Get the OS information from browser agent
     *
     * @version 20090720
     * @return string
     */
    private function getOSinfoGuest()
    {
        $sReturn = null;
        $OSList  = array(
            // Match user agent string with operating systems
            'Windows 3.11'        => 'Win16',
            'Windows 95'          => '(Windows 95)|(Win95)|(Windows_95)',
            'Windows 98'          => '(Windows 98)|(Win98)',
            'Windows 2000'        => '(Windows NT 5.0)|(Windows 2000)',
            'Windows XP'          => '(Windows NT 5.1)|(Windows XP)',
            'Windows Server 2003' => '(Windows NT 5.2)',
            'Windows Vista'       => '(Windows NT 6.0)',
            'Windows 7'           => '(Windows NT 6.1)',
            'Windows NT 4.0'      => '(Windows NT 4.0)|(WinNT4.0)|'
            . '(WinNT)|(Windows NT)',
            'Windows ME'          => 'Windows ME',
            'Open BSD'            => 'OpenBSD',
            'Sun OS'              => 'SunOS',
            'Linux'               => '(Linux)|(X11)',
            'Mac OS'              => '(Mac_PowerPC)|(Macintosh)',
            'QNX'                 => 'QNX',
            'BeOS'                => 'BeOS',
            'OS/2'                => 'OS/2',
            'Search Bot'          => '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)'
            . '|(Slurp)|(MSNBot)|(Ask Jeeves/Teoma)|(ia_archiver)'
        );
        // Loop through the array of user agents and matching operating systems
        foreach ($OSList as $CurrOS => $Match) {
            // Find a match
            if (preg_match($Match, $_SERVER['HTTP_USER_AGENT'])) {
                // We found the correct match
                $sReturn = $CurrOS;
                break;
            }
        }
        return $sReturn;
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
        foreach ($internal_array as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (is_array($value2)) {
                        foreach ($value2 as $key3 => $value3) {
                            $aReturn[$key][$key2][$key3] = $this->setStringCleaned($value3);
                        }
                    } else {
                        $aReturn[$key][$key2] = $this->setStringCleaned($value2);
                    }
                }
            } else {
                $aReturn[$key] = $this->setStringCleaned($value);
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
    public function setFooter($sql_statistics = null, $mySqlVersion = null)
    {
        if ($_SERVER['SERVER_NAME'] == 'lugoj2') {
            include 'piwik_counter.php';
        }
        $sReturn[] = '<!-- Footer | start -->' . $this->setClearBoth1px()
            . '<hr/>';
        switch (APP_INDICATIVE) {
            case 'urgente':
                break;
            default:
                if (@$_SESSION['username'] == 'E303778') {
                    $infoStyle = 'visibility: show;';
                } else {
                    $infoStyle = 'visibility: hidden;';
                }
                $sReturn[] = $this->setStringIntoShortTag('img'
                    , array('src'     => 'images/info.gif', 'alt'     => 'info'
                    , 'style'   => 'float: left;' . $infoStyle
                    , 'onclick' => 'showhide(\'ro06info\');'));
                /**
                 * version_compare()
                 * returns -1 if the first version is lower than the second
                 * , 0 if they are equal, and +1 if the second is lower.
                 */
                if (version_compare(PHP_VERSION, '5.0.0') == -1) {
                    $mm = null;
                } else {
                    $mm = round((memory_get_usage(true) / 1024), 2);
                }
                if ($mySqlVersion != null) {
                    $m = $mySqlVersion;
                }
                $sReturn[] = $this->setFooterInfo2($sql_statistics, $m, $mm);
                break;
        }
        $sReturn[] = $this->setStringIntoTag('&copy; by ' . COPYRIGHT_HOLDER
            , 'div', array('style' => 'float: right;'));
        $sReturn[] = '<!-- Footer | end --></body></html>';
        return implode('', $sReturn);
    }
    /*
     * Returns a table with Server/Client parameters
     * (very usefull for debugging)
     *
     * @version 20100113
     * @param float $querySec
     * @param string $mySqlVer
     * @param float @mem
     * @return string
     */

    private function setFooterInfo2($querySec, $mySqlVer, $mem)
    {
        global $sql_queries;
        global $db;
        $ss = explode(' ', $_SERVER['SERVER_SOFTWARE']);
        foreach ($ss as $value) {
            if (strpos($value, 'Apache') !== false) {
                $info['WebServer'] = explode('/', $value);
            }
            if (strpos($value, 'PHP') !== false) {
                $info['Script'] = explode('/', $value);
            }
            if (strpos($value, 'SVN') !== false) {
                $info['Versioning'] = explode('/', $value);
            }
        }
        $sRow[] = $this->setFooterInfoSection('Server', array(
            0 => array('Operating System', $this->getOSinfo(), '')
            , 1 => array('Web server', $info['WebServer'][0]
                , $info['WebServer'][1])
            , 2 => array('Database server', 'MySQL'
                , $mySqlVer)
            , 3 => array('Scripting engine', $info['Script'][0]
                , $info['Script'][1])
            , 4 => array('Versioning engine', $info['Versioning'][0]
                , $info['Versioning'][1])
            , 5 => array('Date', date('d.m.Y'), '')
            , 6 => array('Time', date('H:i:s'), '')
            , 7 => array('IP address', $_SERVER['SERVER_ADDR'], '')
            )
        );
        $sRow[] = $this->setFooterInfoSection('Client'
            , array(0 => array('Operating System', $this->getOSinfoGuest(), '')
            , 1 => array('Browser agent', $_SERVER['HTTP_USER_AGENT'], '')
            , 2 => array('IP address', $_SERVER['REMOTE_ADDR'], '')
            )
        );
        $sRow[] = $this->setFooterInfoSection('Statistics', array(
            0 => array('Memory allocated', $mem, 'Kb')
            , 1 => array('Page generation time'
                , round((microtime(true) - $this->time_start), 5)
                , 'seconds')
            , 2 => array('Number of queries', $sql_queries, '')
            , 3 => array('Time consumed on queries', $querySec, 'seconds')
            )
        );
        return $this->setStringIntoTag(implode('', $sRow), 'table'
                , array('id'    => 'ro06info'
                , 'style' => 'float: left; display: none;'));
    }
    /*
     * Returns a row to be used in Footer Info
     *
     * @param string $sLabel
     * @param string $sAmount
     * @param string $sUm
     * @version 20100313
     * @return string
     */

    private function setFooterInfoRow($sLabel, $sAmount, $sUm)
    {
        if (is_null($sAmount) || ($sAmount == '')) {
            return '';
        } else {
            $sCell[] = $this->setStringIntoTag($sLabel, 'td');
            if ($sUm == '') {
                $sCell[] = $this->setStringIntoTag($sAmount, 'td'
                    , array('colspan' => 2));
            } else {
                $sCell[] = $this->setStringIntoTag($sAmount, 'td'
                    , array('style' => 'width:50px;'));
                $sCell[] = $this->setStringIntoTag($sUm, 'td');
            }
            return $this->setStringIntoTag(implode('', $sCell), 'tr');
        }
    }
    /*
     * Returns a section in Footer Info
     *
     * @param string $sName
     * @param array $sContent
     * @version 20100313
     * @return string
     */

    private function setFooterInfoSection($sName, $sContent)
    {
        $sCell[] = $this->setStringIntoTag($sName, 'td'
            , array('colspan' => 3
            , 'style'   => 'background-color: black; color: white;'));
        $sRow[]  = $this->setStringIntoTag(implode('', $sCell), 'tr');
        $sCell   = null;
        foreach ($sContent as $value) {
            $sRow[] = $this->setFooterInfoRow($value[0], $value[1], $value[2]);
        }
        return implode('', $sRow);
    }

    /**
     * Generates the page footer - light version
     *
     * @version 20090119
     * @return string
     */
    public function setFooterLight()
    {
        return PHP_EOL . '<hr/></body></html>';
    }

    /**
     * Generates the page header
     *
     * @version 20080423
     * @param string $sPageTitle
     * @return string
     */
    public function setHeader($sPageTitle, $aFeatures = null)
    {
        $sReturn[] = '<!DOCTYPE html PUBLIC '
            . '"-//W3C//DTD XHTML 1.0 Transitional//EN" '
            . '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
            . '<html xmlns="http://www.w3.org/1999/xhtml">'
            . '<head>';
        $sReturn[] = $this->setStringIntoShortTag('meta'
            , array('http-equiv' => 'Content-Language'
            , 'content'    => 'ro-RO'));
        if (strpos($_SERVER["HTTP_ACCEPT"]
                , 'application/xhtml+xml') === false) {
            $sReturn[] = $this->setStringIntoShortTag('meta'
                , array('http-equiv' => 'Content-Type'
                , 'content'    => 'text/html; charset=utf-8'));
        } else {
            $sReturn[] = $this->setStringIntoShortTag('meta'
                , array('http-equiv' => 'Content-Type'
                , 'content'    => 'application/xhtml+xml; charset=utf-8'));
        }
        $sReturn[]    = $this->setStringIntoTag(APP_NAME . ' ' . APP_VERSION
            . ' ' . $sPageTitle, 'title');
        $sReturn[]    = $this->setStringIntoShortTag('link'
            , array('href' => '/new/images/favicon.gif'
            , 'rel'  => 'icon', 'type' => 'image/gif'));
        $sReturnCss[] = 'css=common.css';
        $sReturnCss[] = 'css0=screen.css';
        $sReturnJs[]  = 'js=showhide.js';
        if (isset($aFeatures)) {
            $css_counter = 0;
            $js_counter  = 0;
            foreach ($aFeatures as $key => $value) {
                switch ($key) {
                    case 'css':
                    case 'css_print':
                    case 'css_screen':
                        if (is_array($value)) {
                            foreach ($value as $value2) {
                                $css_counter += 1;
                                $sReturnCss[] = 'css' . $css_counter . '='
                                    . $value2;
                            }
                        } else {
                            $css_counter += 1;
                            $sReturnCss[] = 'css' . $css_counter . '=' . $value;
                        }
                        break;
                    case 'css_ie':
                        $sReturn[] = '<!--[if IE]>' . PHP_EOL
                            . '<link href="/new/styles/' . $value . '"'
                            . ' rel="stylesheet" type="text/css" '
                            . ' media="screen" />' . PHP_EOL
                            . '<![endif]-->';
                        break;
                    case 'css_ie7':
                        $sReturn[] = '<!--[if lt IE 7]>' . PHP_EOL
                            . '<link href="/new/styles/' . $value . '"'
                            . ' rel="stylesheet" type="text/css" '
                            . ' media="screen" />' . PHP_EOL
                            . '<![endif]-->';
                        break;
                    case 'css_own':
                        if (is_array($value)) {
                            foreach ($value as $value2) {
                                $sReturn[] = '<link href="'
                                    . $value2 . '"'
                                    . ' rel="stylesheet" type="text/css" />';
                            }
                        } else {
                            $sReturn[] = '<link href="'
                                . $value . '"'
                                . ' rel="stylesheet" type="text/css" />';
                        }
                        break;
                    case 'javascript':
                        if (is_array($value)) {
                            foreach ($value as $value2) {
                                $js_counter += 1;
                                $sReturnJs[] = 'js' . $js_counter . '='
                                    . $value2;
                            }
                        } else {
                            $js_counter += 1;
                            $sReturnJs[] = 'js' . $js_counter . '=' . $value;
                        }
                        break;
                    case 'javascript_own':
                        if (is_array($value)) {
                            foreach ($value as $value2) {
                                $sReturn[] = '<script src="'
                                    . $value2 . '"'
                                    . ' type="text/javascript"></script>';
                            }
                        } else {
                            $sReturn[] = '<script src="'
                                . $value . '"'
                                . ' type="text/javascript"></script>';
                        }
                        break;
                    case 'refresh':
                        $sReturn[] = '<meta http-equiv="refresh" content="'
                            . $value . '" />';
                        break;
                }
            }
        }
        $sReturn[] = $this->setStringIntoShortTag('link'
            , array('href' => '/new/styles/styles.php?'
            . implode('&amp;', $sReturnCss)
            , 'rel'  => 'stylesheet', 'type' => 'text/css'));
        $sReturn[] = $this->setStringIntoTag('', 'script'
            , array('src'  => '/new/scripts/js.php?'
            . implode('&amp;', $sReturnJs)
            , 'type' => 'text/javascript'));
        $sReturn[] = '</head><body>';
        return implode('', $sReturn) . PHP_EOL;
    }

    /**
     * Generates the page header - light version
     *
     * @version 20090119
     * @return string
     */
    public function setHeaderLight($sPageTitle)
    {
        $sReturn[] = '<html><head>';
        $sReturn[] = $this->setStringIntoTag($sPageTitle, 'title');
        $sReturn[] = $this->setStringIntoShortTag('link'
            , array('href' => '/new/styles/styles.php?css1=common.css'
            . '&amp;css2=tight.css'
            , 'rel'  => 'stylesheet', 'type' => 'text/css'));
        $sReturn[] = '</head><body>';
        return implode('', $sReturn) . PHP_EOL;
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
        $sReturn = '<tr>';
        $sReturn .= $this->setSingleCell($sRowClass, $first_column
            , 'text-align: left;');
        $sReturn .= $this->setSingleCell($sRowClass
            , $this->setDividedResult($row[1], 1
                , array(0, $this->sDecimalPoint, $this->sThousandsSep)
        ));
        $sReturn .= $this->setSingleCell($sRowClass
            , $this->setDividedResult($row[2], 1
                , array(0, $this->sDecimalPoint, $this->sThousandsSep)
        ));
        $v       = $this->setDividedResult($row[12], 1
            , array(0, $this->sDecimalPoint
            , $this->sThousandsSep));
        if ($row[12] != 0) {
            $info = $this->thisFile;
            if (($info['basename'] == 'app.dashboard.php') && (isset($row[7]))) {
                $arguments = '';
                switch ($_GET['feature']) {
                    case 'basic':
                        $arguments = 'ServerId=' . $row[7];
                        break;
                    case 'dashboard':
                        $arguments = 'ServerId=' . $row[7]
                            . '&amp;DbId=' . $row[9];
                        break;
                    case 'detailed':
                        $arguments = 'ServerId=' . $row[7]
                            . '&amp;DbId=' . $row[10]
                            . '&amp;TableId=' . $row[11];
                        break;
                }
                $vfx = '<a href="app.fix.overhead.php?' . $arguments . '" '
                    . 'title="Optimize">' . $this->setIcons('support')
                    . '</a>&nbsp;';
            } else {
                $vfx = '';
            }
            $v = '<span class="warning">' . $vfx . $v . '</span>';
        }
        $sReturn .= $this->setSingleCell($sRowClass, $v);
        $sReturn .= $this->setSingleCell($sRowClass
            , $this->setDividedResult($row[3], 1
                , array(0, $this->sDecimalPoint, $this->sThousandsSep)
        ));
        $sReturn .= $this->setSingleCell($sRowClass
            , ($this->setDividedResult(($row[3] * 100), $row[2]
                , array(2, $this->sDecimalPoint, $this->sThousandsSep))
            . '%'));
        $sReturn .= $this->setSingleCell($sRowClass
            , $this->setDividedResult(($row[2] + $row[12] + $row[3])
                , 1, array(0, $this->sDecimalPoint, $this->sThousandsSep)));
        $sReturn .= $this->setSingleCell($sRowClass
            , $this->setDividedResult($row[2], $row[1]
                , array(2, $this->sDecimalPoint, $this->sThousandsSep)));
        $sReturn .= $this->setSingleCell($sRowClass
            , $this->setDividedResult($row[3], $row[1]
                , array(2, $this->sDecimalPoint, $this->sThousandsSep)));
        $sReturn .= $this->setSingleCell($sRowClass
            , $this->setDividedResult(($row[2] + $row[12] + $row[3])
                , $row[1], array(2, $this->sDecimalPoint
                , $this->sThousandsSep)));
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
