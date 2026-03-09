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

        // wp_register_style(
        //     'cgm-style',
        //     CGM_URL . 'assets/reset.css'
        // );
    }

    public function admin_assets($hook) {

        if (strpos($hook, 'custom-gps-maps') === false) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'cgm-admin',
            CGM_URL . 'assets/admin.js',
            ['jquery'],
            '1.0',
            true
        );
    }
}