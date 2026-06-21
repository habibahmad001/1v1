<?php
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- variables in template files inherited from controller render() scope
/**
 * @var $addon \Wpae\AddonAPI\PMXE_Addon_Base
 * @var $groups array
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?>
<optgroup label="<?php echo esc_attr( $addon->name() ); ?>">
    <?php
    foreach ( $groups as $key => $group ) {
        foreach ( $group['fields'] as $field ) {
            ?>
            <option value="<?php echo 'cf_' . esc_attr( $field['key'] ); ?>"
                    data-type="<?php echo esc_attr( $field['type'] ); ?>"><?php echo esc_html( $field['label'] ); ?></option>
            <?php
        }
    }
    ?>
</optgroup>
