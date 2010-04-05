<?php
// initialize ob_gzhandler function to send and compress data
ob_start ("compress");
// send the requisite header information and character set
header('Content-type: text/javascript; charset: UTF-8');
// set variable for duration of cached content
$offset = 60 * 60 * 24 * 7; // Cache for 31 days
// check cached credentials and reprocess accordingly
header ('Cache-Control: max-age=' . $offset . ', must-revalidate');
// set variable specifying format of expiration header
$expire = "expires: " . gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT";
function compress($buffer) {
	/* remove comments */
	$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	/* remove tabs, spaces, newlines, etc. */
	$buffer = str_replace(array("\t", "\r", "\r\n", "    ", "   ", "  ")
		, '', $buffer);
	$buffer = str_replace(array(' + ', '+ ', ' +'), '+', $buffer);
	$buffer = str_replace(array(' = ', '= ', ' ='), '=', $buffer);
	$buffer = str_replace(array(' += ', '+= ', ' +='), '+=', $buffer);
	$buffer = str_replace(array(' * ', '* ', ' *'), '*', $buffer);
	$buffer = str_replace(array(' , ', ', ', ' ,'), ',', $buffer);
	return $buffer;
}
/* your Js files */
foreach($_GET as $value) {
	include_once $value;
}
ob_end_flush();