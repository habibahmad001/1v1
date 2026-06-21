<?php

namespace Wpae\AddonAPI;

if ( ! defined( 'ABSPATH' ) ) exit;

class PMXE_Addon_Time_Field extends PMXE_Addon_Field {

    public function toString() {
        if ( ! $this->value ) {
            return '';
        }
        $format = $this->settings['time_format'] ?? 'H:i:s';

        return wp_date( $format, strtotime( $this->value ) );
    }
}
