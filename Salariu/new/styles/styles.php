<?php
	header('Content-type: text/css; charset: UTF-8');
	$offset = 60 * 60 * 24 * 31; // Cache for 31 days
	header ('Cache-Control: max-age=' . $offset . ', must-revalidate');
	header ('Expires: ' . gmdate ("D, d M Y H:i:s", time() + $offset) . ' GMT');
	ob_start("compress");
	function compress($buffer) {
		/* remove comments */
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
		/* remove tabs, spaces, newlines, etc. */
		$buffer = str_replace(
			array("\r\n", "\r", "\n", "\t", '  ', '    ', '    ')
			, '', $buffer);
		return $buffer;
	}
	/* your css files */
	foreach($_GET as $value) {
		include_once $value;
	}
	ob_end_flush();