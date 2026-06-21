<?php

defined( 'ABSPATH' ) || exit;

	
function pmxe_init()
{
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only request inspection for connection check
	if(!empty($_GET['check_connection'])) {
	    exit(json_encode(array('success' => true)));
    }
}