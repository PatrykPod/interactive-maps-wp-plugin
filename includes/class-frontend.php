<?php

class CGM_Frontend {

    public function __construct() {

        add_shortcode(
            'custom_gps_maps',
            [ $this, 'render_shortcode' ]
        );
    }

    public function render_shortcode( $atts = [] ) {
        $atts = shortcode_atts(
            [
                'id' => 0,
                'slug' => '',
            ],
            $atts,
            'custom_gps_maps'
        );

        $map = ! empty( $atts['id'] ) ? CGM_DB::get_map( (int) $atts['id'] ) : null;

        if ( ! $map && ! empty( $atts['slug'] ) ) {
            $map = CGM_DB::get_map_by_slug( sanitize_title( $atts['slug'] ) );
        }

        if ( ! $map ) {
            $maps = CGM_DB::get_maps();
            $map = ! empty( $maps ) ? $maps[0] : null;
        }

        if ( ! $map ) {
            return '';
        }

        wp_enqueue_script('cgm-map');
        wp_enqueue_style('cgm-style');

        wp_localize_script(
            'cgm-map',
            'CUSTOM_GPS_MAP',
            [
                'points' => CGM_DB::get_points( $map['id'] ),
                'image'  => CGM_Helper::get_map_image_url( $map['id'] ),
                'pinColor' => CGM_Helper::get_default_pin_color( $map['id'] ),
            ]
        );

        ob_start();

        include CGM_PATH . 'public/views/shortcode.php';

        return ob_get_clean();
    }
}
