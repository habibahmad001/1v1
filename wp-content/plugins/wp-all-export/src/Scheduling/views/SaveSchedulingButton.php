<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<div class="wpae-save-button button wpallexport-large-button"
     style="position: relative; width: 285px; margin-left: 5px; ">

    <div class="save-text"
         style=" user-select: none; display: flex; align-content: center; justify-content: center;">
        <?php if($this->isWizard || (isset($post['enable_real_time_exports']) && $post['enable_real_time_exports'])) {?>
            <?php esc_html_e('Save & Run Export', 'wp-all-export'); ?>
        <?php } else { ?>
            <?php esc_html_e('Save Export Configuration', 'wp-all-export'); ?>
        <?php } ?>
    </div>
</div>