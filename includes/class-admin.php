<?php

class CGM_Admin {

    public function __construct() {

        add_action('admin_menu', [$this, 'menu']);

        add_action('admin_post_custom_gps_maps_add_point', [$this, 'add_point']);
        add_action('admin_post_custom_gps_maps_delete_point', [$this, 'delete_point']);

        // AJAX endpoint
        add_action('wp_ajax_cgm_add_point', [$this, 'ajax_add_point']);
    }


    public function menu() {

        add_menu_page(
            'Custom GPS Maps',
            'Custom GPS Maps',
            'manage_options',
            'custom-gps-maps',
            [$this, 'admin_page']
        );

        add_submenu_page(
            'custom-gps-maps',
            'Map View',
            'Map View',
            'manage_options',
            'custom-gps-maps-map-view',
            [$this, 'map_view']
        );
    }


    public function admin_page() {

        $points = CGM_DB::get_points();

        include CGM_PATH . 'admin/views/admin-page.php';
    }


    public function map_view() {

        include CGM_PATH . 'admin/views/map-view.php';
    }


    public function add_point() {

        CGM_DB::add_point([
            'pointName' => sanitize_text_field($_POST['point_name']),
            'x' => floatval($_POST['x']),
            'y' => floatval($_POST['y'])
        ]);

        wp_redirect(admin_url('admin.php?page=custom-gps-maps'));
        exit;
    }


    public function delete_point() {

        CGM_DB::delete_point(intval($_GET['id']));

        wp_redirect(admin_url('admin.php?page=custom-gps-maps'));
        exit;
    }


    /**
     * AJAX: add point from canvas click
     */
    public function ajax_add_point() {

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $id = CGM_DB::add_point([
            'pointName' => '',
            'x' => floatval($_POST['x']),
            'y' => floatval($_POST['y'])
        ]);

        wp_send_json_success([
            'id' => $id
        ]);
    }
}