<?php

class CGM_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'menu' ] );

        add_action( 'admin_post_custom_gps_maps_add_map', [ $this, 'add_map' ] );
        add_action( 'admin_post_custom_gps_maps_save_map_settings', [ $this, 'save_map_settings' ] );
        add_action( 'admin_post_custom_gps_maps_add_point', [ $this, 'add_point' ] );
        add_action( 'admin_post_custom_gps_maps_update_point', [ $this, 'update_point' ] );
        add_action( 'admin_post_custom_gps_maps_delete_point', [ $this, 'delete_point' ] );

        add_action( 'wp_ajax_cgm_add_point', [ $this, 'ajax_add_point' ] );
    }

    public function menu() {
        add_menu_page(
            'Custom GPS Maps',
            'Custom GPS Maps',
            'manage_options',
            'custom-gps-maps',
            [ $this, 'admin_page' ]
        );
    }

    public function admin_page() {
        $map_id = isset( $_GET['map_id'] ) ? (int) $_GET['map_id'] : 0;

        if ( $map_id <= 0 ) {
            $maps = CGM_DB::get_maps();
            include CGM_PATH . 'admin/views/project-list.php';
            return;
        }

        $map = CGM_DB::get_map( $map_id );

        if ( ! $map ) {
            wp_die( 'Map project not found.' );
        }

        $points = CGM_DB::get_points( $map_id );
        $dimensions = CGM_Helper::get_image_dimensions( $map_id );
        include CGM_PATH . 'admin/views/map-view.php';
    }

    public function add_map() {
        $name = sanitize_text_field( $_POST['map_name'] ?? '' );
        $name = '' !== $name ? $name : 'Untitled map';
        $slug = CGM_Helper::build_map_slug( $name );

        $map_id = CGM_DB::add_map(
            [
                'name' => $name,
                'slug' => $slug,
                'map_image_id' => 0,
                'default_pin_color' => '#ff0000',
            ]
        );

        wp_redirect( CGM_Helper::get_project_view_url( $map_id ) );
        exit;
    }

    public function save_map_settings() {
        $map_id = isset( $_POST['map_id'] ) ? (int) $_POST['map_id'] : 0;
        $map = CGM_DB::get_map( $map_id );

        if ( ! $map ) {
            wp_die( 'Map project not found.' );
        }

        $name = sanitize_text_field( $_POST['map_name'] ?? '' );
        $name = '' !== $name ? $name : $map['name'];
        $slug = CGM_Helper::build_map_slug( $name, $map_id );
        $image_id = $this->sanitize_image_attachment_id( $_POST['custom_gps_maps_image_id'] ?? 0 );
        $pin_color = $this->sanitize_pin_color( $_POST['custom_gps_maps_default_pin_color'] ?? '#ff0000', '#ff0000' );

        CGM_DB::update_map(
            $map_id,
            [
                'name' => $name,
                'slug' => $slug,
                'map_image_id' => $image_id,
                'default_pin_color' => $pin_color,
            ]
        );

        wp_redirect( CGM_Helper::get_project_view_url( $map_id ) );
        exit;
    }

    public function add_point() {
        $map_id = isset( $_POST['map_id'] ) ? (int) $_POST['map_id'] : 0;
        $map = CGM_DB::get_map( $map_id );

        if ( ! $map ) {
            wp_die( 'Map project not found.' );
        }

        $point_id = CGM_DB::add_point( $this->prepare_point_payload( $_POST, $map_id ) );
        $this->maybe_assign_default_point_name( $point_id );

        wp_redirect( CGM_Helper::get_project_view_url( $map_id ) );
        exit;
    }

    public function update_point() {
        $point_id = isset( $_POST['point_id'] ) ? (int) $_POST['point_id'] : 0;
        $map_id = isset( $_POST['map_id'] ) ? (int) $_POST['map_id'] : 0;
        $point = CGM_DB::get_point( $point_id );
        $map = CGM_DB::get_map( $map_id );

        if ( ! $point || ! $map ) {
            wp_die( 'Point or map project not found.' );
        }

        CGM_DB::update_point( $point_id, $this->prepare_point_payload( $_POST, $map_id, $point_id ) );
        $this->maybe_assign_default_point_name( $point_id );

        wp_redirect( CGM_Helper::get_project_view_url( $map_id ) );
        exit;
    }

    public function delete_point() {
        $point_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
        $map_id = isset( $_GET['map_id'] ) ? (int) $_GET['map_id'] : 0;

        CGM_DB::delete_point( $point_id );

        wp_redirect( CGM_Helper::get_project_view_url( $map_id ) );
        exit;
    }

    public function ajax_add_point() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $map_id = isset( $_POST['map_id'] ) ? (int) $_POST['map_id'] : 0;
        $map = CGM_DB::get_map( $map_id );

        if ( ! $map ) {
            wp_send_json_error( 'Map project not found' );
        }

        $point_id = CGM_DB::add_point(
            [
                'map_id' => $map_id,
                'pointName' => '',
                'x' => floatval( $_POST['x'] ?? 0 ),
                'y' => floatval( $_POST['y'] ?? 0 ),
                'pin_icon_id' => 0,
                'pin_icon_scale' => 50,
                'pin_icon_opacity' => 100,
                'pin_color' => CGM_Helper::get_default_pin_color( $map_id ),
                'image_id' => 0,
                'audio_id' => 0,
                'url' => '',
            ]
        );

        $this->maybe_assign_default_point_name( $point_id );

        $point = CGM_DB::get_point( $point_id );

        if ( ! $point ) {
            wp_send_json_error( 'Point not found after creation' );
        }

        $display_index = count( CGM_DB::get_points( $map_id ) );

        wp_send_json_success(
            [
                'id' => $point_id,
                'point' => $point,
                'displayIndex' => $display_index,
                'cardHtml' => $this->render_point_card_html( $point, $display_index, $map_id, 'map' ),
            ]
        );
    }

    private function render_point_card_html( $point, $display_index, $map_id, $redirect_view ) {
        $map = CGM_DB::get_map( $map_id );

        if ( ! $map ) {
            return '';
        }

        ob_start();
        $default_name = 'point-' . (int) $point['id'];
        include CGM_PATH . 'admin/views/point-form.php';
        $form_html = ob_get_clean();

        ob_start();
        ?>
        <div class="cgm-point-card" id="cgm-point-card-<?php echo esc_attr( (int) $point['id'] ); ?>" data-point-id="<?php echo esc_attr( (int) $point['id'] ); ?>">
            <?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php

        return ob_get_clean();
    }

    private function prepare_point_payload( $source, $map_id, $point_id = 0 ) {
        $map_default_pin_color = CGM_Helper::get_default_pin_color( $map_id );
        $point_name = sanitize_text_field( $source['point_name'] ?? '' );
        $content_type = $this->sanitize_content_type( $source['point_content_type'] ?? 'url' );
        $pin_icon_id = $this->sanitize_image_attachment_id( $source['point_pin_icon_id'] ?? 0 );
        $pin_icon_scale = $this->sanitize_pin_icon_scale( $source['point_pin_icon_scale'] ?? 50 );
        $pin_icon_opacity = $this->sanitize_pin_icon_opacity( $source['point_pin_icon_opacity'] ?? 100 );
        $pin_color = $this->sanitize_pin_color( $source['point_pin_color'] ?? $map_default_pin_color, $map_default_pin_color );
        $image_id = $this->sanitize_image_attachment_id( $source['point_image_id'] ?? 0 );
        $audio_id = $this->sanitize_audio_attachment_id( $source['point_audio_id'] ?? 0 );
        $url = $this->sanitize_point_url( $source['point_url'] ?? '' );

        if ( 'image' !== $content_type ) {
            $image_id = 0;
        }

        if ( 'audio' !== $content_type ) {
            $audio_id = 0;
        }

        if ( 'url' !== $content_type ) {
            $url = '';
        }

        if ( '' === $point_name && $point_id > 0 ) {
            $point_name = $this->get_default_point_name( $point_id );
        }

        return [
            'map_id' => $map_id,
            'pointName' => $point_name,
            'x' => floatval( $source['x'] ?? 0 ),
            'y' => floatval( $source['y'] ?? 0 ),
            'pin_icon_id' => $pin_icon_id,
            'pin_icon_scale' => $pin_icon_scale,
            'pin_icon_opacity' => $pin_icon_opacity,
            'pin_color' => $pin_color,
            'image_id' => $image_id,
            'audio_id' => $audio_id,
            'url' => $url,
        ];
    }

    private function sanitize_point_url( $url ) {
        $url = trim( (string) $url );

        return '' === $url ? '' : esc_url_raw( $url );
    }

    private function sanitize_image_attachment_id( $attachment_id ) {
        return $this->sanitize_attachment_id_by_mime_prefix( $attachment_id, 'image/' );
    }

    private function sanitize_audio_attachment_id( $attachment_id ) {
        return $this->sanitize_attachment_id_by_mime_prefix( $attachment_id, 'audio/' );
    }

    private function sanitize_attachment_id_by_mime_prefix( $attachment_id, $mime_prefix ) {
        $attachment_id = (int) $attachment_id;

        if ( $attachment_id <= 0 ) {
            return 0;
        }

        $mime_type = get_post_mime_type( $attachment_id );

        if ( ! $mime_type || strpos( $mime_type, $mime_prefix ) !== 0 ) {
            return 0;
        }

        return $attachment_id;
    }

    private function sanitize_content_type( $content_type ) {
        $content_type = sanitize_key( (string) $content_type );

        if ( ! in_array( $content_type, [ 'url', 'image', 'audio' ], true ) ) {
            return 'url';
        }

        return $content_type;
    }

    private function sanitize_pin_icon_scale( $scale ) {
        $scale = (int) $scale;

        if ( $scale < 1 ) {
            return 1;
        }

        if ( $scale > 200 ) {
            return 200;
        }

        return $scale;
    }

    private function sanitize_pin_icon_opacity( $opacity ) {
        $opacity = (int) $opacity;

        if ( $opacity < 0 ) {
            return 0;
        }

        if ( $opacity > 100 ) {
            return 100;
        }

        return $opacity;
    }

    private function sanitize_pin_color( $color, $fallback ) {
        return CGM_Helper::sanitize_color_value( $color, $fallback );
    }

    private function maybe_assign_default_point_name( $point_id ) {
        $point = CGM_DB::get_point( $point_id );

        if ( ! $point ) {
            return;
        }

        $current_name = isset( $point['pointName'] ) ? trim( (string) $point['pointName'] ) : '';

        if ( '' !== $current_name ) {
            return;
        }

        CGM_DB::update_point(
            $point_id,
            [
                'map_id' => $point['map_id'],
                'pointName' => $this->get_default_point_name( $point_id ),
                'x' => $point['x'],
                'y' => $point['y'],
                'pin_icon_id' => $point['pin_icon_id'],
                'pin_icon_scale' => $point['pin_icon_scale'],
                'pin_icon_opacity' => $point['pin_icon_opacity'],
                'pin_color' => $point['pin_color'],
                'image_id' => $point['image_id'],
                'audio_id' => $point['audio_id'],
                'url' => $point['url'],
            ]
        );
    }

    private function get_default_point_name( $point_id ) {
        return 'point-' . (int) $point_id;
    }
}
