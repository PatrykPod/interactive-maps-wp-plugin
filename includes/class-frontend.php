<?php
class CGM_Frontend {

    public function __construct() {
        add_shortcode( 'custom_gps_maps', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode() {

        wp_enqueue_script( 'cgm-map' );
        wp_enqueue_style( 'cgm-style' );

        wp_localize_script( 'cgm-map', 'CUSTOM_GPS_MAP', [
            'points' => CGM_DB::get_points(),
            'image'  => CGM_Helper::get_map_image_url(),
        ]);

        ob_start();
        include CGM_PATH . 'public/views/shortcode.php';
        return ob_get_clean();
    }
}