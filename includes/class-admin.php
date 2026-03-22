<?php

class CGM_Admin {

    public function __construct() {

        add_action('admin_menu', [$this, 'menu']);

        add_action('admin_post_custom_gps_maps_save_image', [$this, 'save_image']);
        add_action('admin_post_custom_gps_maps_save_pin_color', [$this, 'save_pin_color']);
        add_action('admin_post_custom_gps_maps_add_point', [$this, 'add_point']);
        add_action('admin_post_custom_gps_maps_update_point', [$this, 'update_point']);
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
        $points = CGM_DB::get_points();

        include CGM_PATH . 'admin/views/map-view.php';
    }

    public function save_image() {
        $attachment_id = isset( $_POST['custom_gps_maps_image_id'] ) ? (int) $_POST['custom_gps_maps_image_id'] : 0;

        CGM_Helper::set_map_image_id( $attachment_id );

        wp_redirect( admin_url( 'admin.php?page=custom-gps-maps' ) );
        exit;
    }

    public function save_pin_color() {
        $color = isset( $_POST['custom_gps_maps_default_pin_color'] ) ? (string) $_POST['custom_gps_maps_default_pin_color'] : '#ff0000';

        CGM_Helper::set_default_pin_color( $color );

        wp_redirect( admin_url( 'admin.php?page=custom-gps-maps' ) );
        exit;
    }


    public function add_point() {
        $point_id = CGM_DB::add_point(
            $this->prepare_point_payload( $_POST )
        );

        $this->maybe_assign_default_point_name( $point_id );

        $redirect_page = ! empty( $_POST['redirect_page'] ) ? sanitize_key( $_POST['redirect_page'] ) : 'custom-gps-maps';

        wp_redirect(admin_url('admin.php?page=' . $redirect_page));
        exit;
    }


    public function update_point() {

        $point_id = isset( $_POST['point_id'] ) ? (int) $_POST['point_id'] : 0;

        if ( $point_id > 0 ) {
            CGM_DB::update_point( $point_id, $this->prepare_point_payload( $_POST, $point_id ) );
            $this->maybe_assign_default_point_name( $point_id );
        }

        $redirect_page = ! empty( $_POST['redirect_page'] ) ? sanitize_key( $_POST['redirect_page'] ) : 'custom-gps-maps';

        wp_redirect(admin_url('admin.php?page=' . $redirect_page));
        exit;
    }


    public function delete_point() {

        CGM_DB::delete_point(intval($_GET['id']));

        $redirect_page = ! empty( $_GET['redirect_page'] ) ? sanitize_key( $_GET['redirect_page'] ) : 'custom-gps-maps';

        wp_redirect(admin_url('admin.php?page=' . $redirect_page));
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
            'y' => floatval($_POST['y']),
            'pin_icon_id' => 0,
            'pin_icon_scale' => 50,
            'pin_color' => CGM_Helper::get_default_pin_color(),
            'image_id' => 0,
            'audio_id' => 0,
            'url' => '',
        ]);

        $this->maybe_assign_default_point_name( $id );

        $point = CGM_DB::get_point( $id );

        if ( ! $point ) {
            wp_send_json_error( 'Point not found after creation' );
        }

        $point['pinIconUrl']   = $point['pin_icon_id'] ? wp_get_attachment_url( $point['pin_icon_id'] ) : '';
        $point['pinIconScale'] = isset( $point['pin_icon_scale'] ) ? (int) $point['pin_icon_scale'] : 50;
        $point['pinColor']     = isset( $point['pin_color'] ) ? (string) $point['pin_color'] : CGM_Helper::get_default_pin_color();
        $point['imageUrl']     = $point['image_id'] ? wp_get_attachment_url( $point['image_id'] ) : '';
        $point['audioPath']    = $point['audio_id'] ? wp_get_attachment_url( $point['audio_id'] ) : '';

        $points = CGM_DB::get_points();
        $display_index = count( $points );

        wp_send_json_success([
            'id' => $id,
            'point' => $point,
            'displayIndex' => $display_index,
            'cardHtml' => $this->render_point_card_html( $point, $display_index, 'custom-gps-maps-map-view' ),
        ]);
    }

    private function render_point_card_html( $point, $display_index, $redirect_page ) {
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

    private function prepare_point_payload( $source, $point_id = 0 ) {
        $point_name   = sanitize_text_field( $source['point_name'] ?? '' );
        $content_type = $this->sanitize_content_type( $source['point_content_type'] ?? 'url' );
        $pin_icon_id  = $this->sanitize_image_attachment_id( $source['point_pin_icon_id'] ?? 0 );
        $pin_icon_scale = $this->sanitize_pin_icon_scale( $source['point_pin_icon_scale'] ?? 50 );
        $pin_color    = $this->sanitize_pin_color( $source['point_pin_color'] ?? CGM_Helper::get_default_pin_color() );
        $image_id     = $this->sanitize_image_attachment_id( $source['point_image_id'] ?? 0 );
        $audio_id     = $this->sanitize_audio_attachment_id( $source['point_audio_id'] ?? 0 );
        $url          = $this->sanitize_point_url( $source['point_url'] ?? '' );

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
            'pointName' => $point_name,
            'x' => floatval( $source['x'] ?? 0 ),
            'y' => floatval( $source['y'] ?? 0 ),
            'pin_icon_id' => $pin_icon_id,
            'pin_icon_scale' => $pin_icon_scale,
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

    private function sanitize_pin_color( $color ) {
        $color = sanitize_hex_color( (string) $color );

        return $color ? $color : CGM_Helper::get_default_pin_color();
    }

    private function maybe_assign_default_point_name( $point_id ) {
        $point_id = (int) $point_id;

        if ( $point_id <= 0 ) {
            return;
        }

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
                'pointName' => $this->get_default_point_name( $point_id ),
                'x' => $point['x'],
                'y' => $point['y'],
                'pin_icon_id' => $point['pin_icon_id'],
                'pin_icon_scale' => isset( $point['pin_icon_scale'] ) ? (int) $point['pin_icon_scale'] : 50,
                'pin_color' => isset( $point['pin_color'] ) ? (string) $point['pin_color'] : CGM_Helper::get_default_pin_color(),
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
