<?php
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- variables in template files inherited from controller render() scope
/**
 * @var $addon \Wpae\AddonAPI\PMXE_Addon_Base
 * @var $groups array
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?>
<?php foreach ( $groups as $key => $group ) { ?>
    <optgroup
            label="<?php echo esc_attr( $addon->name() ); ?> - <?php echo esc_attr( $group['label'] ); ?>">
        <?php
        foreach ( $group['fields'] as $field ) {
            $field_options = serialize( array_merge( $field, [ 'group_id' => $group['id'] ] ) );
            ?>
            <option value="<?php echo esc_attr( $addon->slug ); ?>" label="<?php echo esc_attr( $field['key'] ); ?>"
                    options="<?php echo esc_attr( $field_options ); ?>"><?php echo esc_html( $field['label'] ); ?></option>
            <?php
        }
        ?>
    </optgroup>
<?php } ?>
