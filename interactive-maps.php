<?php
/**
 * Plugin Name: Interactive Maps
 * Description: GPS-like map made from your graphic where you pin interactive points by your choice. Canvas API is used.
 * Version: 1.0
 * Author: Patryk Podgórny
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CustomGpsMaps {

    private $admin;

    public function __construct() {

        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        add_shortcode( 'custom_gps_maps', [ $this, 'shortcode' ] );

        $this->admin = new CustomGpsMaps_Admin( $this );
    }

    public function admin_menu() {

        add_menu_page(
            'Custom GPS Maps',
            'Custom GPS Maps',
            'manage_options',
            'custom-gps-maps',
            [ $this->admin, 'admin_view' ]
        );

        add_submenu_page(
            'custom-gps-maps',
            'Map View',
            'Map View',
            'manage_options',
            'custom-gps-maps-map-view',
            [ 'CustomGpsMaps_Admin', 'map_view' ]
        );
    }

    /**
     * Shortcode output (NO echo!)
     */
    public function shortcode() {
        wp_enqueue_script( 'custom-gps-map' );
        wp_enqueue_style( 'custom-gps-map' );

        wp_localize_script(
            'custom-gps-map',
            'CUSTOM_GPS_MAP',
            [
                'points' => custom_gps_maps_get_points(),
                'image'  => custom_gps_maps_get_map_image_url(),
            ]
        );

        ob_start();
        ?>
        <div class="custom-gps-map-wrapper">
            <canvas id="myCanvas"></canvas>

            <div class="map-controls">
                <button id="zoomInButton">+</button>
                <button id="zoomOutButton">−</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
  }

}