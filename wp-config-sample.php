<?php
/**
 * WordPress Configuration Sample
 *
 * Copy this file to wp-config.php and fill in your values.
 * DO NOT commit wp-config.php — it contains live credentials.
 */

// ── Performance ────────────────────────────────────────────────────────────
define( 'WP_MEMORY_LIMIT', '512M' );

// ── Redis object cache ─────────────────────────────────────────────────────
define( 'WP_REDIS_HOST',         '127.0.0.1' );
define( 'WP_REDIS_PORT',         6379 );
define( 'WP_REDIS_TIMEOUT',      1 );
define( 'WP_REDIS_READ_TIMEOUT', 1 );
define( 'WP_REDIS_DATABASE',     0 );

// ── Cron (replaced by real server crontab — see README) ────────────────────
// Disable WP-Cron — replaced by a real server-side cron (every 5 minutes).
define( 'DISABLE_WP_CRON', true );

// ── WP 2FA encryption key ──────────────────────────────────────────────────
define( 'WP2FA_ENCRYPT_KEY', 'REPLACE_WITH_YOUR_KEY' );

// ── Database ───────────────────────────────────────────────────────────────
define( 'DB_NAME',     'wordpress' );
define( 'DB_USER',     'wordpress' );
define( 'DB_PASSWORD', 'REPLACE_WITH_DB_PASSWORD' );
define( 'DB_HOST',     'localhost' );
define( 'DB_CHARSET',  'utf8' );
define( 'DB_COLLATE',  '' );

// ── Authentication keys and salts ──────────────────────────────────────────
// Generate fresh values at: https://api.wordpress.org/secret-key/1.1/salt/
define( 'AUTH_KEY',         'REPLACE_WITH_UNIQUE_PHRASE' );
define( 'SECURE_AUTH_KEY',  'REPLACE_WITH_UNIQUE_PHRASE' );
define( 'LOGGED_IN_KEY',    'REPLACE_WITH_UNIQUE_PHRASE' );
define( 'NONCE_KEY',        'REPLACE_WITH_UNIQUE_PHRASE' );
define( 'AUTH_SALT',        'REPLACE_WITH_UNIQUE_PHRASE' );
define( 'SECURE_AUTH_SALT', 'REPLACE_WITH_UNIQUE_PHRASE' );
define( 'LOGGED_IN_SALT',   'REPLACE_WITH_UNIQUE_PHRASE' );
define( 'NONCE_SALT',       'REPLACE_WITH_UNIQUE_PHRASE' );

// ── Table prefix ───────────────────────────────────────────────────────────
$table_prefix = 'wp_';

// ── JWT Auth ───────────────────────────────────────────────────────────────
define( 'JWT_AUTH_SECRET_KEY',  'REPLACE_WITH_JWT_SECRET' );
define( 'JWT_AUTH_CORS_ENABLE', true );

// ── Debug (enable only in local/dev) ───────────────────────────────────────
define( 'WP_DEBUG', false );

// ── File editing (disable in production for security) ──────────────────────
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true );

/* Stop editing here. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
