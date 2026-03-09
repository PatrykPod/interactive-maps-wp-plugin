<?php
class CGM_DB {

    public static function get_points() {
        global $wpdb;
        $table = $wpdb->prefix . 'points';

        return $wpdb->get_results(
            "SELECT id, pointName, x, y FROM $table",
            ARRAY_A
        );
    }

    public static function add_point( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'points';

        return $wpdb->insert( $table, $data );
    }

    public static function delete_point( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'points';

        return $wpdb->delete( $table, [ 'id' => $id ] );
    }
}