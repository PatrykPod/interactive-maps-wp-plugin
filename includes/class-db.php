<?php

class CGM_DB {

    public static function get_maps() {
        global $wpdb;

        $table = $wpdb->prefix . 'cgm_maps';
        $maps = $wpdb->get_results( "SELECT id, name, slug, map_image_id, default_pin_color, created_at FROM $table ORDER BY id ASC", ARRAY_A );

        foreach ( $maps as &$map ) {
            $map['id'] = (int) $map['id'];
            $map['map_image_id'] = (int) $map['map_image_id'];
            $map['image_url'] = $map['map_image_id'] ? wp_get_attachment_url( $map['map_image_id'] ) : '';
            $map['default_pin_color'] = CGM_Helper::sanitize_color_value( $map['default_pin_color'], '#ff0000' );
            $map['shortcode'] = sprintf( '[custom_gps_maps id="%d"]', (int) $map['id'] );
        }
        unset( $map );

        return $maps;
    }

    public static function get_map( $map_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'cgm_maps';
        $map = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name, slug, map_image_id, default_pin_color, created_at FROM $table WHERE id = %d",
                $map_id
            ),
            ARRAY_A
        );

        if ( ! $map ) {
            return null;
        }

        $map['id'] = (int) $map['id'];
        $map['map_image_id'] = (int) $map['map_image_id'];
        $map['image_url'] = $map['map_image_id'] ? wp_get_attachment_url( $map['map_image_id'] ) : '';
        $map['default_pin_color'] = CGM_Helper::sanitize_color_value( $map['default_pin_color'], '#ff0000' );
        $map['shortcode'] = sprintf( '[custom_gps_maps id="%d"]', (int) $map['id'] );

        return $map;
    }

    public static function get_map_by_slug( $slug ) {
        global $wpdb;

        $table = $wpdb->prefix . 'cgm_maps';
        $map = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name, slug, map_image_id, default_pin_color, created_at FROM $table WHERE slug = %s",
                $slug
            ),
            ARRAY_A
        );

        if ( ! $map ) {
            return null;
        }

        return self::get_map( (int) $map['id'] );
    }

    public static function add_map( $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'cgm_maps';
        $wpdb->insert(
            $table,
            [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'map_image_id' => isset( $data['map_image_id'] ) ? (int) $data['map_image_id'] : 0,
                'default_pin_color' => isset( $data['default_pin_color'] ) ? $data['default_pin_color'] : '#ff0000',
            ],
            [ '%s', '%s', '%d', '%s' ]
        );

        return (int) $wpdb->insert_id;
    }

    public static function update_map( $map_id, $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'cgm_maps';
        return $wpdb->update(
            $table,
            [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'map_image_id' => isset( $data['map_image_id'] ) ? (int) $data['map_image_id'] : 0,
                'default_pin_color' => isset( $data['default_pin_color'] ) ? $data['default_pin_color'] : '#ff0000',
            ],
            [ 'id' => $map_id ],
            [ '%s', '%s', '%d', '%s' ],
            [ '%d' ]
        );
    }

    public static function get_points( $map_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'points';
        $points = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, map_id, pointName, x, y, pin_icon_id, pin_icon_scale, pin_icon_opacity, pin_color, image_id, audio_id, url FROM $table WHERE map_id = %d ORDER BY id ASC",
                $map_id
            ),
            ARRAY_A
        );

        foreach ( $points as &$point ) {
            $point = self::hydrate_point_row( $point );
        }
        unset( $point );

        return $points;
    }

    public static function get_point( $id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'points';
        $point = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, map_id, pointName, x, y, pin_icon_id, pin_icon_scale, pin_icon_opacity, pin_color, image_id, audio_id, url FROM $table WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if ( ! $point ) {
            return null;
        }

        return self::hydrate_point_row( $point );
    }

    public static function add_point( $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'points';
        $wpdb->insert(
            $table,
            [
                'map_id' => (int) $data['map_id'],
                'pointName' => $data['pointName'],
                'x' => $data['x'],
                'y' => $data['y'],
                'pin_icon_id' => isset( $data['pin_icon_id'] ) ? (int) $data['pin_icon_id'] : 0,
                'pin_icon_scale' => isset( $data['pin_icon_scale'] ) ? (int) $data['pin_icon_scale'] : 50,
                'pin_icon_opacity' => isset( $data['pin_icon_opacity'] ) ? (int) $data['pin_icon_opacity'] : 100,
                'pin_color' => isset( $data['pin_color'] ) ? $data['pin_color'] : '#ff0000',
                'image_id' => isset( $data['image_id'] ) ? (int) $data['image_id'] : 0,
                'audio_id' => isset( $data['audio_id'] ) ? (int) $data['audio_id'] : 0,
                'url' => isset( $data['url'] ) ? $data['url'] : '',
            ],
            [ '%d', '%s', '%f', '%f', '%d', '%d', '%d', '%s', '%d', '%d', '%s' ]
        );

        return (int) $wpdb->insert_id;
    }

    public static function update_point( $id, $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'points';
        return $wpdb->update(
            $table,
            [
                'map_id' => (int) $data['map_id'],
                'pointName' => $data['pointName'],
                'x' => $data['x'],
                'y' => $data['y'],
                'pin_icon_id' => isset( $data['pin_icon_id'] ) ? (int) $data['pin_icon_id'] : 0,
                'pin_icon_scale' => isset( $data['pin_icon_scale'] ) ? (int) $data['pin_icon_scale'] : 50,
                'pin_icon_opacity' => isset( $data['pin_icon_opacity'] ) ? (int) $data['pin_icon_opacity'] : 100,
                'pin_color' => isset( $data['pin_color'] ) ? $data['pin_color'] : '#ff0000',
                'image_id' => isset( $data['image_id'] ) ? (int) $data['image_id'] : 0,
                'audio_id' => isset( $data['audio_id'] ) ? (int) $data['audio_id'] : 0,
                'url' => isset( $data['url'] ) ? $data['url'] : '',
            ],
            [ 'id' => $id ],
            [ '%d', '%s', '%f', '%f', '%d', '%d', '%d', '%s', '%d', '%d', '%s' ],
            [ '%d' ]
        );
    }

    public static function delete_point( $id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'points';
        return $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
    }

    private static function hydrate_point_row( $point ) {
        $point['id'] = (int) $point['id'];
        $point['map_id'] = (int) $point['map_id'];
        $point['pin_icon_id'] = (int) $point['pin_icon_id'];
        $point['pin_icon_scale'] = isset( $point['pin_icon_scale'] ) ? (int) $point['pin_icon_scale'] : 50;
        $point['pin_icon_opacity'] = isset( $point['pin_icon_opacity'] ) ? (int) $point['pin_icon_opacity'] : 100;
        $point['pin_color'] = CGM_Helper::sanitize_color_value( $point['pin_color'] ?? '', '#ff0000' );
        $point['image_id'] = (int) $point['image_id'];
        $point['audio_id'] = (int) $point['audio_id'];
        $point['url'] = isset( $point['url'] ) ? (string) $point['url'] : '';
        $point['pinIconUrl'] = $point['pin_icon_id'] ? wp_get_attachment_url( $point['pin_icon_id'] ) : '';
        $point['pinIconScale'] = $point['pin_icon_scale'];
        $point['pinIconOpacity'] = $point['pin_icon_opacity'];
        $point['pinColor'] = $point['pin_color'];
        $point['imageUrl'] = $point['image_id'] ? wp_get_attachment_url( $point['image_id'] ) : '';
        $point['audioPath'] = $point['audio_id'] ? wp_get_attachment_url( $point['audio_id'] ) : '';

        return $point;
    }
}
