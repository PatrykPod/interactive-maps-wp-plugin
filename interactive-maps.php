<?php
/*
Plugin Name: Custom GPS Maps
Description: LATEST!!! Interactive image-based maps with points
Version: 1.2
*/

if ( ! defined( 'ABSPATH' ) ) exit;

/* Plugin constants */
define( 'CGM_PATH', plugin_dir_path( __FILE__ ) );
define( 'CGM_URL', plugin_dir_url( __FILE__ ) );

// Autoload (simple version)
require_once __DIR__ . '/includes/class-plugin.php';
require_once __DIR__ . '/includes/class-admin.php';
require_once __DIR__ . '/includes/class-frontend.php';
require_once __DIR__ . '/includes/class-db.php';
require_once __DIR__ . '/includes/class-assets.php';
require_once __DIR__ . '/includes/helpers.php';

// Boot plugin
add_action( 'plugins_loaded', function () {
    new CGM_Plugin();
});