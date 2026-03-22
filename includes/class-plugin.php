<?php

class CGM_Plugin {

    public function __construct() {

        new CGM_Admin();
        new CGM_Frontend();
        new CGM_Assets();

        add_action( 'init', [ $this, 'maybe_upgrade_schema' ] );
        add_filter( 'upload_mimes', [ $this, 'allow_pinpoint_upload_mimes' ] );
        add_filter( 'wp_check_filetype_and_ext', [ $this, 'fix_svg_filetype' ], 10, 4 );

        register_activation_hook( CGM_PATH . 'interactive-maps.php', [ $this, 'activate' ] );
    }

    public function activate() {
        $this->create_tables();
        $this->migrate_legacy_single_map_data();
    }

    public function maybe_upgrade_schema() {
        $this->create_tables();
        $this->migrate_legacy_single_map_data();
    }

    private function create_tables() {
        global $wpdb;

        $maps_table = $wpdb->prefix . 'cgm_maps';
        $points_table = $wpdb->prefix . 'points';
        $charset = $wpdb->get_charset_collate();

        $maps_sql = "CREATE TABLE $maps_table (
            id INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(200) NOT NULL,
            map_image_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            default_pin_color VARCHAR(32) NOT NULL DEFAULT '#ff0000',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY slug (slug)
        ) $charset;";

        $points_sql = "CREATE TABLE $points_table (
            id INT NOT NULL AUTO_INCREMENT,
            map_id INT NOT NULL DEFAULT 0,
            pointName VARCHAR(255),
            x FLOAT NOT NULL,
            y FLOAT NOT NULL,
            pin_icon_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            pin_icon_scale SMALLINT UNSIGNED NOT NULL DEFAULT 50,
            pin_icon_opacity SMALLINT UNSIGNED NOT NULL DEFAULT 100,
            pin_color VARCHAR(32) NOT NULL DEFAULT '#ff0000',
            image_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            audio_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            url TEXT NULL,
            PRIMARY KEY (id),
            KEY map_id (map_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $maps_sql );
        dbDelta( $points_sql );
    }

    private function migrate_legacy_single_map_data() {
        global $wpdb;

        $maps_table = $wpdb->prefix . 'cgm_maps';
        $points_table = $wpdb->prefix . 'points';

        $maps_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $maps_table" );

        if ( $maps_count > 0 ) {
            return;
        }

        $legacy_image_id = (int) get_option( 'custom_gps_maps_image_id', 0 );
        $legacy_pin_color = get_option( 'custom_gps_maps_default_pin_color', '#ff0000' );
        $legacy_pin_color = CGM_Helper::sanitize_color_value( $legacy_pin_color, '#ff0000' );

        $has_legacy_points = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $points_table" ) > 0;

        if ( ! $legacy_image_id && ! $has_legacy_points ) {
            return;
        }

        $map_id = CGM_DB::add_map(
            [
                'name' => 'Map 1',
                'slug' => 'map-1',
                'map_image_id' => $legacy_image_id,
                'default_pin_color' => $legacy_pin_color,
            ]
        );

        if ( $map_id > 0 ) {
            $wpdb->query( $wpdb->prepare( "UPDATE $points_table SET map_id = %d WHERE map_id = 0", $map_id ) );
        }
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
