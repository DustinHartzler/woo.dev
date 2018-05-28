<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'Of72FLIa0+YKhJTQUBCVffZR35g/k6NGJCe+W8yxk5fUbYBRPdk5xyamK3pJAO2mTe336rx1TdrzOQCWqW2fcg==');
define('SECURE_AUTH_KEY',  'kNEPC2FeZypmCGYMSiPpkeyQx3l5yrMOpWvPJXbPEroPR8yTYQYwg6lZLXNEb+vTtt8OeXlDbjlbRenWqxQ3dA==');
define('LOGGED_IN_KEY',    'BxzETnaAhIxNHxxWDB4TU856UrlEYqEIAOGjDM45q0c8GgZ073q1dXFAvON8WWBv+D5quBwyVrP6LXlgrxKA0A==');
define('NONCE_KEY',        't0pDxWUFtbDo5FCEN80NYzWHFPnMjK5HUfpkHvRKgdkN8gHPUnt7yE7qmrn+7ph+j9VdbmD7Mv+riH3gW8lZnA==');
define('AUTH_SALT',        '7kSsXMzCiRZVCEI1ZnASmU2lXuWON39vYn/9WF9DFN3N3/DrzRaj3875v9yC6nDKHO/I5zphO9hXUXfoVXxR6A==');
define('SECURE_AUTH_SALT', 'IWeqOdS6dot0GgYoUqbcZQbP2UEWubXFuZ6Ip/ghyBIN8P1FnGnI411UK1rijyE+TOrn4DN+Yso9Wl62KRfRsg==');
define('LOGGED_IN_SALT',   'JtRCf7y/r+70S3UaASiUDJk+b950UpytMm4v+TCW47zHlr5Y7yP1mONVzoJxGOOyD6WCOamqx9hOEzSn12Cnkw==');
define('NONCE_SALT',       'iJoTxNINyns32Ig7qnR7BOLig2dgv78zdMrOeo8JSEj2PGUnHll/tX/IIik7/sAjKqk2VtsgU8OqcolgL9SMAw==');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';





/* Inserted by Local by Flywheel. See: http://codex.wordpress.org/Administration_Over_SSL#Using_a_Reverse_Proxy */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

/* Inserted by Local by Flywheel. Fixes $is_nginx global for rewrites. */
if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'Flywheel/' ) !== false ) {
	$_SERVER['SERVER_SOFTWARE'] = 'nginx/1.10.1';
}
/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
