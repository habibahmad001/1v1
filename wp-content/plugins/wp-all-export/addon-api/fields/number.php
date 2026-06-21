<?php

namespace Wpae\AddonAPI;

if ( ! defined( 'ABSPATH' ) ) exit;

class PMXE_Addon_Number_Field extends PMXE_Addon_Field {

    public function toString() {
        return $this->value;
    }
}
