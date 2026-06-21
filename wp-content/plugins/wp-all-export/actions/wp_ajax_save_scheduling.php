<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;


use Wpae\Scheduling\Interval\ScheduleTime;
use Wpae\Scheduling\Scheduling;

/**
 * @throws Exception
 */
function pmxe_wp_ajax_save_scheduling()
{

    if (!check_ajax_referer('wp_all_export_secure', 'security', false)) {
        exit(esc_html__('Security check', 'wp-all-export'));
    }

    if (!current_user_can(PMXE_Plugin::$capabilities)) {
        exit(esc_html__('Security check', 'wp-all-export'));
    }

    $elementId = isset( $_POST['element_id'] ) ? absint( $_POST['element_id'] ) : 0;

    $post = $_POST;

    foreach($post['scheduling_times'] as $schedulingTime) {
        if(!preg_match('/^(0?[1-9]|1[012])(:[0-5]\d)[APap][mM]$/', $schedulingTime) && $schedulingTime != '') {
            header('HTTP/1.1 400 Bad request', true, 400);
            die('Invalid times provided');
        }
    }

    try{
        $scheduling = Scheduling::create();
        $scheduling->handleScheduling($elementId, $post);
    } catch (\Wpae\Scheduling\Exception\SchedulingHttpException $e) {
        header('HTTP/1.1 503 Service unavailable', true, 503);
        echo json_encode(array('success' => false));

        die;
    }

    $export = new PMXE_Export_Record();
    $export->getById($elementId);
    $export->set(array('options' => array_merge($export->options, $post)));
    $export->save();

    echo json_encode(array('success' => true));
    die;
}
