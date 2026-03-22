<?php
$point = isset( $point ) ? $point : [];
$redirect_page = isset( $redirect_page ) ? $redirect_page : 'custom-gps-maps';
$display_index = isset( $display_index ) ? (int) $display_index : 0;

$point_id      = isset( $point['id'] ) ? (int) $point['id'] : 0;
$default_name  = $point_id ? 'point-' . $point_id : ( isset( $default_name ) ? $default_name : '' );
$point_name    = isset( $point['pointName'] ) && '' !== trim( (string) $point['pointName'] ) ? $point['pointName'] : $default_name;
$point_x       = isset( $point['x'] ) ? $point['x'] : '';
$point_y       = isset( $point['y'] ) ? $point['y'] : '';
$point_url     = isset( $point['url'] ) ? $point['url'] : '';
$pin_icon_id   = isset( $point['pin_icon_id'] ) ? (int) $point['pin_icon_id'] : 0;
$pin_icon_scale = isset( $point['pin_icon_scale'] ) ? (int) $point['pin_icon_scale'] : 50;
$pin_color     = isset( $point['pin_color'] ) && sanitize_hex_color( $point['pin_color'] ) ? $point['pin_color'] : CGM_Helper::get_default_pin_color();
$image_id      = isset( $point['image_id'] ) ? (int) $point['image_id'] : 0;
$audio_id      = isset( $point['audio_id'] ) ? (int) $point['audio_id'] : 0;
$pin_icon_url  = $pin_icon_id ? wp_get_attachment_url( $pin_icon_id ) : '';
$image_url     = $image_id ? wp_get_attachment_url( $image_id ) : '';
$audio_url     = $audio_id ? wp_get_attachment_url( $audio_id ) : '';
$form_action   = $point_id ? 'custom_gps_maps_update_point' : 'custom_gps_maps_add_point';
$content_type  = 'url';

if ( $image_id ) {
    $content_type = 'image';
} elseif ( $audio_id ) {
    $content_type = 'audio';
} elseif ( '' !== trim( (string) $point_url ) ) {
    $content_type = 'url';
}
?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cgm-point-form" data-active-type="<?php echo esc_attr( $content_type ); ?>">
    <input type="hidden" name="action" value="<?php echo esc_attr( $form_action ); ?>">
    <input type="hidden" name="redirect_page" value="<?php echo esc_attr( $redirect_page ); ?>">
    <input type="hidden" name="point_content_type" value="<?php echo esc_attr( $content_type ); ?>" class="cgm-content-type-input">

    <?php if ( $point_id ) : ?>
        <input type="hidden" name="point_id" value="<?php echo esc_attr( $point_id ); ?>">
    <?php endif; ?>

    <?php if ( $display_index > 0 ) : ?>
        <h3>#<?php echo esc_html( $display_index ); ?></h3>
    <?php endif; ?>

    <p>
        <label>
            Name:<br>
            <input type="text" name="point_name" value="<?php echo esc_attr( $point_name ); ?>" class="regular-text">
        </label>
    </p>

    <div class="cgm-coordinates">
        <p>
            <label>
                X:<br>
                <input type="number" step="0.01" name="x" value="<?php echo esc_attr( $point_x ); ?>">
            </label>
        </p>

        <p>
            <label>
                Y:<br>
                <input type="number" step="0.01" name="y" value="<?php echo esc_attr( $point_y ); ?>">
            </label>
        </p>
    </div>

    <div class="cgm-media-field cgm-pin-icon-field">
        <input type="hidden" name="point_pin_icon_id" value="<?php echo esc_attr( $pin_icon_id ); ?>" class="cgm-media-input">
        <p><strong>Pin icon</strong></p>
        <div class="cgm-image-preview <?php echo $pin_icon_url ? '' : 'is-empty'; ?>">
            <?php if ( $pin_icon_url ) : ?>
                <img src="<?php echo esc_url( $pin_icon_url ); ?>" alt="" style="max-width:80px;height:auto;">
            <?php endif; ?>
        </div>
        <p class="description">Leave empty to use the colored default point.</p>
        <p>
            <button type="button" class="button cgm-media-button" data-media-type="image" data-media-label="pin icon">
                <?php echo $pin_icon_id ? 'Replace pin icon' : 'Select pin icon'; ?>
            </button>
            <button type="button" class="button cgm-clear-media <?php echo $pin_icon_id ? '' : 'is-hidden'; ?>">
                Remove pin icon
            </button>
        </p>
        <p class="cgm-range-field">
            <label>
                Icon size:
                <input type="range" name="point_pin_icon_scale" min="1" max="200" step="1" value="<?php echo esc_attr( $pin_icon_scale ); ?>" class="cgm-range-input">
                <span class="cgm-range-value"><?php echo esc_html( $pin_icon_scale ); ?>%</span>
            </label>
        </p>
        <p class="cgm-point-color-field <?php echo $pin_icon_id ? 'is-hidden' : ''; ?>">
            <label>
                Pin color:<br>
                <input
                    type="text"
                    name="point_pin_color"
                    value="<?php echo esc_attr( $pin_color ); ?>"
                    class="cgm-color-picker cgm-point-color-picker"
                    data-default-color="<?php echo esc_attr( CGM_Helper::get_default_pin_color() ); ?>">
            </label>
        </p>
    </div>

    <div class="cgm-option-switcher" role="tablist" aria-label="Point content type">
        <button type="button" class="cgm-option-tab <?php echo 'url' === $content_type ? 'is-active' : ''; ?>" data-option-type="url" aria-pressed="<?php echo 'url' === $content_type ? 'true' : 'false'; ?>">
            <span class="dashicons dashicons-admin-links"></span>
            <span class="screen-reader-text">URL</span>
        </button>
        <button type="button" class="cgm-option-tab <?php echo 'image' === $content_type ? 'is-active' : ''; ?>" data-option-type="image" aria-pressed="<?php echo 'image' === $content_type ? 'true' : 'false'; ?>">
            <span class="dashicons dashicons-format-image"></span>
            <span class="screen-reader-text">Image</span>
        </button>
        <button type="button" class="cgm-option-tab <?php echo 'audio' === $content_type ? 'is-active' : ''; ?>" data-option-type="audio" aria-pressed="<?php echo 'audio' === $content_type ? 'true' : 'false'; ?>">
            <span class="dashicons dashicons-format-audio"></span>
            <span class="screen-reader-text">Audio</span>
        </button>
    </div>

    <div class="cgm-option-panel <?php echo 'url' === $content_type ? 'is-active' : ''; ?>" data-option-panel="url">
        <p>
            <label>
                URL:<br>
                <input type="url" name="point_url" value="<?php echo esc_attr( $point_url ); ?>" class="regular-text cgm-url-input" placeholder="https://example.com">
            </label>
        </p>
    </div>

    <div class="cgm-option-panel <?php echo 'image' === $content_type ? 'is-active' : ''; ?>" data-option-panel="image">
        <div class="cgm-media-field">
            <input type="hidden" name="point_image_id" value="<?php echo esc_attr( $image_id ); ?>" class="cgm-media-input">
            <p><strong>Point image</strong></p>
            <div class="cgm-image-preview <?php echo $image_url ? '' : 'is-empty'; ?>">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width:140px;height:auto;">
                <?php endif; ?>
            </div>
            <p class="description">Allowed: PNG, JPG, SVG, WEBP.</p>
            <p>
                <button type="button" class="button cgm-media-button" data-media-type="image" data-media-label="image">
                    <?php echo $image_id ? 'Replace image' : 'Select image'; ?>
                </button>
                <button type="button" class="button cgm-clear-media <?php echo $image_id ? '' : 'is-hidden'; ?>">
                    Remove image
                </button>
            </p>
        </div>
    </div>

    <div class="cgm-option-panel <?php echo 'audio' === $content_type ? 'is-active' : ''; ?>" data-option-panel="audio">
        <div class="cgm-media-field">
            <input type="hidden" name="point_audio_id" value="<?php echo esc_attr( $audio_id ); ?>" class="cgm-media-input">
            <p><strong>Point audio</strong></p>
            <div class="cgm-audio-preview <?php echo $audio_url ? '' : 'is-empty'; ?>">
                <?php if ( $audio_url ) : ?>
                    <audio controls preload="none" src="<?php echo esc_url( $audio_url ); ?>"></audio>
                <?php endif; ?>
            </div>
            <p class="description">Use the WordPress media library to upload or select audio.</p>
            <p>
                <button type="button" class="button cgm-media-button" data-media-type="audio" data-media-label="audio">
                    <?php echo $audio_id ? 'Replace audio' : 'Select audio'; ?>
                </button>
                <button type="button" class="button cgm-clear-media <?php echo $audio_id ? '' : 'is-hidden'; ?>">
                    Remove audio
                </button>
            </p>
        </div>
    </div>

    <p>
        <input type="submit" class="button button-primary" value="<?php echo $point_id ? 'Update point' : 'Add point'; ?>">
        <?php if ( $point_id ) : ?>
            <a class="button button-link-delete" href="<?php echo esc_url( admin_url( 'admin-post.php?action=custom_gps_maps_delete_point&id=' . $point_id . '&redirect_page=' . $redirect_page ) ); ?>">Delete</a>
        <?php endif; ?>
    </p>
</form>
