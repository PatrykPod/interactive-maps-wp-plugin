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

        wp_register_script(
            'cgm-map',
            CGM_URL . 'assets/canvasMaps.js',
            [],
            '1.0',
            true
        );
    }

    public function admin_assets($hook) {
        error_log($hook);

        if (strpos($hook, 'custom-gps-maps') === false) {
            return;
        }

        wp_enqueue_media();

        // Admin uploader JS
        wp_enqueue_script(
            'cgm-admin',
            CGM_URL . 'assets/admin.js',
            ['jquery'],
            '1.0',
            true
        );

        // Register the canvas script
        wp_enqueue_script(
            'cgm-map',
            CGM_URL . 'assets/canvasMaps-admin.js',
            [],
            '1.0',
            true
        );

        wp_localize_script(
            'cgm-map',
            'CUSTOM_GPS_MAP',
            [
                'points' => CGM_DB::get_points(),
                'image'  => CGM_Helper::get_map_image_url(),
                'admin'  => true,
                'ajax'   => admin_url('admin-ajax.php')
            ]
        );
    }
}