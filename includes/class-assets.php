<?php

class CGM_Assets {

    public function __construct() {

        add_action(
            'wp_enqueue_scripts',
            [ $this, 'frontend_assets' ]
        );

        add_action(
            'admin_enqueue_scripts',
            [ $this, 'admin_assets' ]
        );
    }

    public function frontend_assets() {
        $map_script_path = CGM_PATH . 'assets/canvasMaps.js';

        wp_register_script(
            'cgm-map',
            CGM_URL . 'assets/canvasMaps.js',
            [],
            file_exists( $map_script_path ) ? filemtime( $map_script_path ) : '1.0',
            true
        );

        wp_localize_script(
            'cgm-map',
            'CUSTOM_GPS_MAP',
            [
                'points' => CGM_DB::get_points(),
                'image'  => CGM_Helper::get_map_image_url(),
                'pinColor' => CGM_Helper::get_default_pin_color(),
            ]
        );
    }

    public function admin_assets($hook) {
        if (strpos($hook, 'custom-gps-maps') === false) {
            return;
        }

        $admin_script_path = CGM_PATH . 'assets/admin.js';
        $map_admin_script_path = CGM_PATH . 'assets/canvasMaps-admin.js';

        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // Admin uploader JS
        wp_enqueue_script(
            'cgm-admin',
            CGM_URL . 'assets/admin.js',
            ['jquery', 'wp-color-picker'],
            file_exists( $admin_script_path ) ? filemtime( $admin_script_path ) : '1.0',
            true
        );

        // Register the canvas script
        wp_enqueue_script(
            'cgm-map',
            CGM_URL . 'assets/canvasMaps-admin.js',
            [],
            file_exists( $map_admin_script_path ) ? filemtime( $map_admin_script_path ) : '1.0',
            true
        );

        wp_localize_script(
            'cgm-map',
            'CUSTOM_GPS_MAP',
            [
                'points' => CGM_DB::get_points(),
                'image'  => CGM_Helper::get_map_image_url(),
                'pinColor' => CGM_Helper::get_default_pin_color(),
                'admin'  => true,
                'ajax'   => admin_url('admin-ajax.php')
            ]
        );
    }
}
