<?php

class CGM_Helper {

    public static function get_default_pin_color() {
        $color = get_option( 'custom_gps_maps_default_pin_color', '#ff0000' );
        $color = sanitize_hex_color( $color );

        return $color ? $color : '#ff0000';
    }

    public static function set_default_pin_color( $color ) {
        $color = sanitize_hex_color( $color );

        update_option( 'custom_gps_maps_default_pin_color', $color ? $color : '#ff0000' );
    }

    public static function get_map_image_id() {
        return (int) get_option('custom_gps_maps_image_id', 0);
    }

    public static function set_map_image_id($attachment_id) {
        update_option('custom_gps_maps_image_id', (int) $attachment_id);
    }

    public static function get_map_image_url() {
        $id = self::get_map_image_id();
        return $id ? wp_get_attachment_url($id) : '';
    }

    public static function get_image_dimensions() {

        $image_id = self::get_map_image_id();

        if (!$image_id) {
            return false;
        }

        $meta = wp_get_attachment_metadata($image_id);

        if (empty($meta['width']) || empty($meta['height'])) {
            return false;
        }

        return [
            'width'  => (int) $meta['width'],
            'height' => (int) $meta['height'],
        ];
    }
}
