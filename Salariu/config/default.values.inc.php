<?php

foreach ($_REQUEST as $key => $value) {
    if ($value == '') {
        unset($_REQUEST[$key]);
    }
}
if (!isset($_REQUEST['sn'])) {
    $_REQUEST['sn'] = 850;
}
if (!isset($_REQUEST['sc'])) {
    $_REQUEST['sc'] = 0;
}
if (!isset($_REQUEST['pb'])) {
    $_REQUEST['pb'] = 0;
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
?>