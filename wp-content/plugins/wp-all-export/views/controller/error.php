<?php

defined( 'ABSPATH' ) || exit;
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- variables in template files inherited from controller render() scope
 foreach ($errors as $msg): ?>
	<div class="error"><p><?php echo wp_kses_post($msg) ?></p></div>
<?php endforeach ?>