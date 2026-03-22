<?php
class CGM_DB {

    public static function get_points() {
        global $wpdb;
        $table = $wpdb->prefix . 'points';

        $points = $wpdb->get_results(
            "SELECT id, pointName, x, y, pin_icon_id, pin_icon_scale, pin_color, image_id, audio_id, url FROM $table ORDER BY id ASC",
            ARRAY_A
        );

        foreach ( $points as &$point ) {
            $point['pin_icon_id'] = (int) $point['pin_icon_id'];
            $point['pin_icon_scale'] = isset( $point['pin_icon_scale'] ) ? (int) $point['pin_icon_scale'] : 50;
            $point['pin_color'] = isset( $point['pin_color'] ) ? (string) $point['pin_color'] : '#ff0000';
            $point['image_id']  = (int) $point['image_id'];
            $point['audio_id']  = (int) $point['audio_id'];
            $point['pinIconUrl'] = $point['pin_icon_id'] ? wp_get_attachment_url( $point['pin_icon_id'] ) : '';
            $point['pinIconScale'] = $point['pin_icon_scale'];
            $point['pinColor'] = $point['pin_color'];
            $point['imageUrl']  = $point['image_id'] ? wp_get_attachment_url( $point['image_id'] ) : '';
            $point['audioPath'] = $point['audio_id'] ? wp_get_attachment_url( $point['audio_id'] ) : '';
            $point['url']       = isset( $point['url'] ) ? (string) $point['url'] : '';
        }
        unset( $point );

        return $points;
    }

    public static function add_point($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'points';

        $wpdb->insert($table, [
            'pointName' => $data['pointName'],
            'x' => $data['x'],
            'y' => $data['y'],
            'pin_icon_id' => isset( $data['pin_icon_id'] ) ? (int) $data['pin_icon_id'] : 0,
            'pin_icon_scale' => isset( $data['pin_icon_scale'] ) ? (int) $data['pin_icon_scale'] : 50,
            'pin_color' => isset( $data['pin_color'] ) ? $data['pin_color'] : '#ff0000',
            'image_id' => isset( $data['image_id'] ) ? (int) $data['image_id'] : 0,
            'audio_id' => isset( $data['audio_id'] ) ? (int) $data['audio_id'] : 0,
            'url' => isset( $data['url'] ) ? $data['url'] : '',
        ]);

        return $wpdb->insert_id;
    }

    public static function get_point( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'points';

        $point = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, pointName, x, y, pin_icon_id, pin_icon_scale, pin_color, image_id, audio_id, url FROM $table WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if ( ! $point ) {
            return null;
        }

        $point['pin_icon_id'] = (int) $point['pin_icon_id'];
        $point['pin_icon_scale'] = isset( $point['pin_icon_scale'] ) ? (int) $point['pin_icon_scale'] : 50;
        $point['pin_color'] = isset( $point['pin_color'] ) ? (string) $point['pin_color'] : '#ff0000';
        $point['image_id'] = (int) $point['image_id'];
        $point['audio_id'] = (int) $point['audio_id'];
        $point['url']      = isset( $point['url'] ) ? (string) $point['url'] : '';

        return $point;
    }

    public static function update_point( $id, $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'points';

        return $wpdb->update(
            $table,
            [
                'pointName' => $data['pointName'],
                'x' => $data['x'],
                'y' => $data['y'],
                'pin_icon_id' => isset( $data['pin_icon_id'] ) ? (int) $data['pin_icon_id'] : 0,
                'pin_icon_scale' => isset( $data['pin_icon_scale'] ) ? (int) $data['pin_icon_scale'] : 50,
                'pin_color' => isset( $data['pin_color'] ) ? $data['pin_color'] : '#ff0000',
                'image_id' => isset( $data['image_id'] ) ? (int) $data['image_id'] : 0,
                'audio_id' => isset( $data['audio_id'] ) ? (int) $data['audio_id'] : 0,
                'url' => isset( $data['url'] ) ? $data['url'] : '',
            ],
            [ 'id' => $id ],
            [ '%s', '%f', '%f', '%d', '%d', '%s', '%d', '%d', '%s' ],
            [ '%d' ]
        );
    }

    public static function delete_point( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'points';

        return $wpdb->delete( $table, [ 'id' => $id ] );
    }
}
