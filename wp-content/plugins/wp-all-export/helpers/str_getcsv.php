<?php

defined( 'ABSPATH' ) || exit;

if ( ! function_exists('str_getcsv')):
/**
 * str_getcsv function for PHP less than 5.3
 * @see http://php.net/manual/en/function.str-getcsv.php
 * NOTE: function doesn't support escape paramter (in correspondance to fgetcsv not supporting it prior 5.3)
 */
function str_getcsv($input, $delimiter=',', $enclosure='"') {
	if ("" == $delimiter) $delimiter = ',';
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- export plugin reads/writes its own files in uploads dir, WP_Filesystem not viable for streamed CSV/XML output
	$temp = fopen("php://memory", "rw");
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- export plugin reads/writes its own files in uploads dir, WP_Filesystem not viable for streamed CSV/XML output
	fwrite($temp, $input);
	fseek($temp, 0);
	$r = fgetcsv($temp, strlen($input), $delimiter, $enclosure, '\\');
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- export plugin reads/writes its own files in uploads dir, WP_Filesystem not viable for streamed CSV/XML output
	fclose($temp);
	return $r;
}

endif;