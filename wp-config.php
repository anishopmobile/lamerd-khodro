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
define( 'DB_NAME', 'lamerd-khodro' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'j8RYR]&58i!tDO]4' );

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
define( 'AUTH_KEY',         '-HG043.QHqyL[Uz$6H?IC]Pga=31#+PSo[A:(2NC<Hs6~,_L+x/!#-<sVBJt|-w ' );
define( 'SECURE_AUTH_KEY',  'gS(ynT8[{^76X^xX5_=EhjU{C94JAP/Og2d,!6[c *nF/bj@FtJ9&k3>$u@g}Or/' );
define( 'LOGGED_IN_KEY',    '~;8J^$XSkCgr=xbJSAgY8{lFXFK0:6Aq;e+PHr_{5e!yIN}tpLVHi)smz$EE7G0_' );
define( 'NONCE_KEY',        'EdaCSP6.{:Z4(T=#7/Fu1b@qjZkti1Tp/CEnG|/HH!?SDniJ<;edM#79];{6A9,~' );
define( 'AUTH_SALT',        'Y1lPI]#}Q}e9$WfEE?p!yU3!!rSxgfMz=ytCmX(fGF:h[Vcd{nXh3Pc,#vQ+1wsu' );
define( 'SECURE_AUTH_SALT', '1j$0@];@x>,O<evil-=.DeU(N$NOG+P.L(EKU]j_babzkh8k4^zV4l3aq`/!:TF^' );
define( 'LOGGED_IN_SALT',   'D3=CjEo i7|0(+^php8_(-K.+f@Ei{s-.}Ie^M[FJ/pSn UVt(!5XEO0T .%f{&k' );
define( 'NONCE_SALT',       '1Yv,:{L^*eI/SD-F. F=MF&[qjY.x(ai.5~4MSIj>~ifpG{$-XR/CIRcdZWXRDnv' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'ouskhodro_';

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
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
