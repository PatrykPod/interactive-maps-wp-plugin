<?php

class CGM_Helper {

    public static function sanitize_color_value( $color, $fallback = '#ff0000' ) {
        $fallback = trim( (string) $fallback );

        if ( $fallback === $color ) {
            $fallback = '#ff0000';
        } else {
            $fallback = self::sanitize_color_value( $fallback, '#ff0000' );
        }

        $color = trim( (string) $color );

        $hex = sanitize_hex_color( $color );

        if ( $hex ) {
            return $hex;
        }

        if ( preg_match( '/^rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})(?:\s*,\s*((?:0|1)(?:\.\d+)?|0?\.\d+))?\s*\)$/i', $color, $matches ) ) {
            $red = max( 0, min( 255, (int) $matches[1] ) );
            $green = max( 0, min( 255, (int) $matches[2] ) );
            $blue = max( 0, min( 255, (int) $matches[3] ) );
            $alpha = isset( $matches[4] ) && '' !== $matches[4] ? (float) $matches[4] : 1;
            $alpha = max( 0, min( 1, $alpha ) );

            if ( $alpha >= 1 ) {
                return sprintf( '#%02x%02x%02x', $red, $green, $blue );
            }

            $alpha = round( $alpha, 3 );
            $alpha = rtrim( rtrim( number_format( $alpha, 3, '.', '' ), '0' ), '.' );

            return sprintf( 'rgba(%d, %d, %d, %s)', $red, $green, $blue, $alpha );
        }

        return $fallback;
    }

    public static function get_color_components( $color, $fallback = '#ff0000' ) {
        $color = self::sanitize_color_value( $color, $fallback );

        if ( 0 === strpos( $color, '#' ) ) {
            return [
                'hex' => $color,
                'alpha' => 1,
            ];
        }

        if ( preg_match( '/^rgba\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*((?:0|1)(?:\.\d+)?|0?\.\d+)\s*\)$/i', $color, $matches ) ) {
            return [
                'hex' => sprintf(
                    '#%02x%02x%02x',
                    max( 0, min( 255, (int) $matches[1] ) ),
                    max( 0, min( 255, (int) $matches[2] ) ),
                    max( 0, min( 255, (int) $matches[3] ) )
                ),
                'alpha' => max( 0, min( 1, (float) $matches[4] ) ),
            ];
        }

        return [
            'hex' => self::get_color_components( self::sanitize_color_value( $fallback, '#ff0000' ), '#ff0000' )['hex'],
            'alpha' => 1,
        ];
    }

    public static function get_default_pin_color( $map_id = 0 ) {
        $map = $map_id ? CGM_DB::get_map( $map_id ) : null;

        if ( $map && ! empty( $map['default_pin_color'] ) ) {
            return self::sanitize_color_value( $map['default_pin_color'], '#ff0000' );
        }

        $color = get_option( 'custom_gps_maps_default_pin_color', '#ff0000' );

        return self::sanitize_color_value( $color, '#ff0000' );
    }

    public static function get_map_image_id( $map_id ) {
        $map = CGM_DB::get_map( $map_id );

        return $map ? (int) $map['map_image_id'] : 0;
    }

    public static function get_map_image_url( $map_id ) {
        $image_id = self::get_map_image_id( $map_id );

        return $image_id ? wp_get_attachment_url( $image_id ) : '';
    }

    public static function get_image_dimensions( $map_id ) {
        $image_id = self::get_map_image_id( $map_id );

        if ( ! $image_id ) {
            return false;
        }

        $meta = wp_get_attachment_metadata( $image_id );

        if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
            return false;
        }

        return [
            'width' => (int) $meta['width'],
            'height' => (int) $meta['height'],
        ];
    }

    public static function build_map_slug( $name, $exclude_map_id = 0 ) {
        $base_slug = sanitize_title( $name );
        $base_slug = $base_slug ? $base_slug : 'map';
        $slug = $base_slug;
        $suffix = 2;

        while ( true ) {
            $existing = CGM_DB::get_map_by_slug( $slug );

            if ( ! $existing || (int) $existing['id'] === (int) $exclude_map_id ) {
                return $slug;
            }

            $slug = $base_slug . '-' . $suffix;
            $suffix++;
        }
    }

    public static function get_project_view_url( $map_id ) {
        return admin_url( 'admin.php?page=custom-gps-maps&map_id=' . (int) $map_id );
    }
}
