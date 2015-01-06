<?php

if (isset($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        if ($value == '') {
            unset($_REQUEST[$key]);
        }
    }
}
if (!isset($_REQUEST['ym'])) {
    $_REQUEST['ym'] = mktime(0, 0, 0, date('m'), 1, date('Y'));
}
if (!isset($_REQUEST['sn'])) {
    $_REQUEST['sn'] = 975;
}
if (!isset($_REQUEST['sc'])) {
    $_REQUEST['sc'] = 0;
}
if (!isset($_REQUEST['pb'])) {
    $_REQUEST['pb'] = 0;
}
if (!isset($_REQUEST['pi'])) {
    $_REQUEST['pi'] = 0;
}
if (!isset($_REQUEST['pc'])) {
    $_REQUEST['pc'] = 0;
}
if (!isset($_REQUEST['pn'])) {
    $_REQUEST['pn'] = 0;
}
if (!isset($_REQUEST['os175'])) {
    $_REQUEST['os175'] = 0;
}
if (!isset($_REQUEST['os200'])) {
    $_REQUEST['os200'] = 0;
}
if (!isset($_REQUEST['zfb'])) {
    $_REQUEST['zfb'] = 0;
}
if (!isset($_REQUEST['szamnt'])) {
    $_REQUEST['szamnt'] = 0;
}
if (!isset($_REQUEST['gbns'])) {
    $_REQUEST['gbns'] = 0;
}
