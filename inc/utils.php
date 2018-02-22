<?php

function Input($mex) {
	echo $mex . ": ";
	return trim(fgets(STDIN));
}

function XECHO($text) {
	global $log;
	echo $text;
	// $log->Write($text);
}

function Cast($object, $class) {
	$ret = new $class();
	foreach ( $object as $name => $value ) {
		$ret->$name = $value;
	}
	return $ret;
}

/*
 * if (function_exists("xml_parse")) {
 * echo "OK";
 * } else {
 * die("xml missing");
 * }
 */
?>
