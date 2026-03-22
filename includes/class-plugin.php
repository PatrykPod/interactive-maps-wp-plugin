<?php

class CGM_Plugin {

    public function __construct() {

        // Load components
        new CGM_Admin();
        new CGM_Frontend();
        new CGM_Assets();

        add_action( 'init', [ $this, 'maybe_upgrade_schema' ] );
        add_filter( 'upload_mimes', [ $this, 'allow_pinpoint_upload_mimes' ] );
        add_filter( 'wp_check_filetype_and_ext', [ $this, 'fix_svg_filetype' ], 10, 4 );

        // Register activation hook
        register_activation_hook( CGM_PATH . 'interactive-maps.php', [ $this, 'activate' ] );
    }

    public function activate() {
        $this->create_points_table();
    }

    public function maybe_upgrade_schema() {
        $this->create_points_table();
    }

    private function create_points_table() {

        global $wpdb;

        $table = $wpdb->prefix . 'points';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id INT NOT NULL AUTO_INCREMENT,
            pointName VARCHAR(255),
            x FLOAT NOT NULL,
            y FLOAT NOT NULL,
            pin_icon_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            pin_icon_scale SMALLINT UNSIGNED NOT NULL DEFAULT 50,
            pin_color VARCHAR(7) NOT NULL DEFAULT '#ff0000',
            image_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            audio_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            url TEXT NULL,
            PRIMARY KEY (id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public function allow_pinpoint_upload_mimes( $mimes ) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['webp'] = 'image/webp';

        return $mimes;
    }

    public function fix_svg_filetype( $data, $file, $filename, $mimes ) {
        $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

        if ( 'svg' !== $extension ) {
            return $data;
        }

        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';

        return $data;
    }
}
