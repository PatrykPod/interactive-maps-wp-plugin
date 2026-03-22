<?php
$image_id      = CGM_Helper::get_map_image_id();
$image_url     = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
$dimensions    = CGM_Helper::get_image_dimensions();
$default_pin_color = CGM_Helper::get_default_pin_color();
$next_point_id = 1;

if ( ! empty( $points ) ) {
    $point_ids     = wp_list_pluck( $points, 'id' );
    $next_point_id = max( array_map( 'intval', $point_ids ) ) + 1;
}
?>

<div class="wrap">
    <h1>Custom GPS Maps</h1>

    <div class="controller" style="display:flex;align-items:flex-start;gap:35px;flex-wrap:wrap;">
        <div class="left">
            <h2>Map image</h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="custom_gps_maps_save_image">
                <input type="hidden" id="custom-gps-map-image-id" name="custom_gps_maps_image_id" value="<?php echo esc_attr( $image_id ); ?>">

                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:100%;height:auto;" alt="">
                <?php endif; ?>

                <p>
                    <button type="button" class="button" id="custom-gps-map-image-select">
                        <?php echo $image_id ? 'Change map image' : 'Select map image'; ?>
                    </button>
                </p>

                <p>
                    <input type="submit" class="button button-primary" value="Save map image">
                </p>
            </form>

            <h2>Default pin color</h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="custom_gps_maps_save_pin_color">

                <p>
                    <input
                        type="text"
                        name="custom_gps_maps_default_pin_color"
                        value="<?php echo esc_attr( $default_pin_color ); ?>"
                        class="cgm-color-picker"
                        data-default-color="#ff0000">
                </p>

                <p>
                    <input type="submit" class="button button-primary" value="Save default pin color">
                </p>
            </form>
        </div>

        <div class="right">
            <?php if ( $dimensions ) : ?>
                <p class="description">
                    <strong>Coordinate system:</strong><br><br>
                    Width (X): <code>0 - <?php echo esc_html( $dimensions['width'] ); ?></code><br>
                    Height (Y): <code>0 - <?php echo esc_html( $dimensions['height'] ); ?></code>
                </p>
            <?php else : ?>
                <p class="description">
                    Map image not found, so coordinate ranges are unavailable.
                </p>
            <?php endif; ?>

            <h2>Add point</h2>
            <?php
            $point = [
                'pointName' => 'point-' . $next_point_id,
            ];
            $default_name = 'point-' . $next_point_id;
            $display_index = 0;
            $redirect_page = 'custom-gps-maps';
            include CGM_PATH . 'admin/views/point-form.php';
            ?>
        </div>
    </div>

    <div class="list">
        <h2>Saved points</h2>

        <?php if ( empty( $points ) ) : ?>
            <p>No points saved yet.</p>
        <?php else : ?>
            <div class="cgm-point-grid">
                <?php foreach ( $points as $index => $point ) : ?>
                    <div class="cgm-point-card">
                        <?php
                        $display_index = $index + 1;
                        $default_name = 'point-' . (int) $point['id'];
                        $redirect_page = 'custom-gps-maps';
                        include CGM_PATH . 'admin/views/point-form.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .cgm-point-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        margin-top: 16px;
    }

    .cgm-point-card,
    .cgm-point-form {
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 6px;
        padding: 16px;
    }

    .cgm-point-form h3 {
        margin-top: 0;
    }

    .cgm-coordinates {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .cgm-option-switcher {
        display: flex;
        gap: 12px;
        margin: 18px 0 12px;
    }

    .cgm-option-tab {
        width: 42px;
        height: 42px;
        border: 1px solid #d0d6dc;
        border-radius: 999px;
        background: linear-gradient(180deg, #f7f8fa 0%, #eef1f4 100%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        filter: grayscale(1);
        transition: filter 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
    }

    .cgm-option-tab .dashicons {
        font-size: 20px;
        width: 20px;
        height: 20px;
        pointer-events: none;
    }

    .cgm-option-tab[data-option-type="url"].is-active {
        color: #1565c0;
        border-color: #1565c0;
        filter: grayscale(0);
    }

    .cgm-option-tab[data-option-type="image"].is-active {
        color: #2e7d32;
        border-color: #2e7d32;
        filter: grayscale(0);
    }

    .cgm-option-tab[data-option-type="audio"].is-active {
        color: #ef6c00;
        border-color: #ef6c00;
        filter: grayscale(0);
    }

    .cgm-option-tab.is-active {
        transform: translateY(-1px);
    }

    .cgm-option-panel {
        display: none;
        margin: 10px 0 16px;
    }

    .cgm-option-panel.is-active {
        display: block;
    }

    .cgm-media-field {
        margin: 0;
        padding: 12px;
        background: #f6f7f7;
        border-radius: 4px;
    }

    .cgm-image-preview.is-empty,
    .cgm-audio-preview.is-empty {
        display: none;
    }

    .cgm-audio-preview audio {
        width: 100%;
        max-width: 260px;
    }

    .is-hidden {
        display: none !important;
    }
</style>
