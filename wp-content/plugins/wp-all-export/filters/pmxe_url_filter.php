<?php

defined( 'ABSPATH' ) || exit;


function pmxe_url_filter($str) {
    return "http://" == $str || "ftp://" == $str ? "" : $str;
}