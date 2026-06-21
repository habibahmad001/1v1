<?php

namespace Wpae\App\Service;


use Wpae\App\Service\Addons\AddonNotFoundException;

class ScheduledExport
{
    /**
     * @param $export
     * @return JsonResponse
     */
    public function trigger($export)
    {
        if ((int)$export->executing) {
            return new JsonResponse(array(
                'status' => 403,
                /* translators: %s: export ID */
                'message' => sprintf(esc_html__('Export #%s is currently in manually process. Request skipped.', 'wp-all-export'), $export->id)
            ));
        }
        if ($export->processing and !$export->triggered) {
            return new JsonResponse(array(
                'status' => 403,
                /* translators: %s: export ID */
                'message' => sprintf(esc_html__('Export #%s currently in process. Request skipped.', 'wp-all-export'), $export->id)
            ));
        }
        if (!$export->processing and $export->triggered) {
            return new JsonResponse(array(
                'status' => 403,
                'message' => sprintf('Export #%s already triggered. Request skipped.', $export->id)
            ));
        }

        $export->set(array(
            'triggered' => 1,
            'exported' => 0,
            'last_activity' => date('Y-m-d H:i:s')  // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- DB timestamp must match local-timezone format used by Manage Exports UI readers (mysql2date / strtotime / human_time_diff)
        ))->update();

        return new JsonResponse(array(
            'status' => 200,
            /* translators: %s: export ID */
            'message' => sprintf(esc_html__('#%s Cron job triggered.', 'wp-all-export'), $export->id)
        ));
    }

    /**
     * @param $export
     * @param $queue_exports
     * @param $logger
     */
    public function process($export, $queue_exports, $logger)
    {
        if ($export->processing == 1 and (time() - strtotime($export->registered_on)) > 120) { // it means processor crashed, so it will reset processing to false, and terminate. Then next run it will work normally.
            $export->set(array(
                'processing' => 0
            ))->update();
        }

        // start execution imports that is in the cron process
        if (!(int)$export->triggered) {
            if (!empty($export->parent_id) or empty($queue_exports)) {
                wp_send_json(array(
                    'status' => 403,
                    /* translators: %s: export ID */
                    'message' => sprintf(esc_html__('Export #%s is not triggered. Request skipped.', 'wp-all-export'), $export->id)
                ));
            }
        } elseif ((int)$export->executing) {
            wp_send_json(array(
                'status' => 403,
                /* translators: %s: export ID */
                'message' => sprintf(esc_html__('Export #%s is currently in manually process. Request skipped.', 'wp-all-export'), $export->id)
            ));
        } elseif ((int)$export->triggered and !(int)$export->processing) {
            try {
                $response = $export->set(array('canceled' => 0))->execute($logger, true);
            } catch (AddonNotFoundException $e) {
                die(esc_html($e->getMessage()));
            }
            if (!(int)$export->triggered and !(int)$export->processing) {

                // trigger update child exports with correct WHERE & JOIN filters
                if (!empty($export->options['cpt']) and class_exists('WooCommerce') and in_array('shop_order', $export->options['cpt']) and empty($export->parent_id)) {
                    $queue_exports = XmlExportWooCommerceOrder::prepare_child_exports($export, true);

                    if (empty($queue_exports)) {
                        delete_option('wp_all_export_queue_' . $export->id);
                    } else {
                        update_option('wp_all_export_queue_' . $export->id, $queue_exports);
                    }
                }
                // remove child export from queue
                if (!empty($export->parent_id)) {
                    $queue_exports = get_option('wp_all_export_queue_' . $export->parent_id);

                    if (!empty($queue_exports)) {
                        foreach ($queue_exports as $key => $queue_export) {
                            if ($queue_export == $export->id) {
                                unset($queue_exports[$key]);
                            }
                        }
                    }

                    if (empty($queue_exports)) {
                        delete_option('wp_all_export_queue_' . $export->parent_id);
                    } else {
                        update_option('wp_all_export_queue_' . $export->parent_id, $queue_exports);
                    }
                }

                wp_send_json(array(
                    'status' => 200,
                    /* translators: %s: export ID */
                    'message' => sprintf(esc_html__('Export #%s complete', 'wp-all-export'), $export->id)
                ));
            } else {
                wp_send_json(array(
                    'status' => 200,
                    /* translators: %s: number of records processed */
                    'message' => sprintf(esc_html__('Records Processed %s.', 'wp-all-export'), (int)$export->exported)
                ));
            }

        } else {
            wp_send_json(array(
                'status' => 403,
                /* translators: %s: export ID */
                'message' => sprintf(esc_html__('Export #%s already processing. Request skipped.', 'wp-all-export'), $export->id)
            ));
        }
    }
}