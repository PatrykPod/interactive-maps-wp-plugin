<?php

class CGM_Plugin {

    public function __construct() {

        // Load components
        new CGM_Admin();
        new CGM_Frontend();
        new CGM_Assets();

        // Register activation hook
        register_activation_hook( CGM_PATH . 'interactive-maps.php', [ $this, 'activate' ] );
    }

    public function activate() {

        global $wpdb;

        $table = $wpdb->prefix . 'points';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id INT NOT NULL AUTO_INCREMENT,
            pointName VARCHAR(255),
            x FLOAT NOT NULL,
            y FLOAT NOT NULL,
            PRIMARY KEY (id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}