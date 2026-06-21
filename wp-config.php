<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', '1v1' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '>1**{r?Fs.)b.W#O*Mcc;YGiC~4>,{_e7w~ix^Eg;-PjFnau/V)`Z(s[(jT:86Kq' );
define( 'SECURE_AUTH_KEY',  '8}xpZ)&q `Qqe,7o}s?#;5mLY)Mt^1FUmmCTd)[u:V4]Exuw&44X[wA4U#=&_@1l' );
define( 'LOGGED_IN_KEY',    'a}hheXVmOLtt)5WZA7#E;=E/)dBqE1Wy,oh]>LvC+&(T>X8M##2o5gcf.}o!#}-b' );
define( 'NONCE_KEY',        '<qOx9R%zX_MGEtTnm|*n|]3):ZCf.q6=C^fzbU(04mf< e/MTRp0GCbu%iDiZweP' );
define( 'AUTH_SALT',        '%K/S()@ }cS#,G`Yq`2HzO@C[Md{OY^oC^Ox4GSgoB.N|OQE,AmIfB6gdny4SGS`' );
define( 'SECURE_AUTH_SALT', 'c$xP,5C LTT3 s5M9<Eoz<`JDlH~}arJs);a>B4Xl}H.=B&R8o=0OGw&n~6E@{A/' );
define( 'LOGGED_IN_SALT',   '4wp;lOR[ulihzR$q01na&ml9Zco.FR~D-RqSDNb6{UE[^XXX50p{1pI_^KRRuD)B' );
define( 'NONCE_SALT',       'Da@TsSsSa0N**J^hn3r9KY2*99X3J:<m7/Z{D3|1mS{wOH8c)CHS,HU-l~SUFP{i' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
