<?php
$image_id = (int) $map['map_image_id'];
$image_url = ! empty( $map['image_url'] ) ? $map['image_url'] : '';
$default_pin_color = $map['default_pin_color'];
$default_pin_color_components = CGM_Helper::get_color_components( $default_pin_color, '#ff0000' );
$next_point_id = 1;

if ( ! empty( $points ) ) {
    $point_ids = wp_list_pluck( $points, 'id' );
    $next_point_id = max( array_map( 'intval', $point_ids ) ) + 1;
}
?>

<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <h1 style="margin-bottom:6px;"><?php echo esc_html( $map['name'] ); ?></h1>
            <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=custom-gps-maps' ) ); ?>">&larr; Back to projects</a></p>
        </div>
    </div>

    <div class="notice inline" style="margin:16px 0 20px;">
        <p>Shortcode: <code><?php echo esc_html( $map['shortcode'] ); ?></code></p>
    </div>

    <div class="cgm-project-settings">
        <div class="cgm-project-settings-card">
            <h2>Map settings</h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="custom_gps_maps_save_map_settings">
                <input type="hidden" name="map_id" value="<?php echo esc_attr( $map['id'] ); ?>">
                <input type="hidden" id="custom-gps-map-image-id" name="custom_gps_maps_image_id" 
                        value="<?php echo esc_attr( $image_id ); ?>">

                <p>
                    <label>
                        Project name:<br>
                        <input type="text" name="map_name" 
                                value="<?php echo esc_attr( $map['name'] ); ?>" 
                                class="regular-text">
                    </label>
                </p>

                <p>
                    <input type="submit" class="button button-primary" value="Save project settings">
                </p>
            </form>
        </div>
    </div>

    <p class="description">
        Double-click the map to create a new point, then use the forms below to edit it.
    </p>

    <div style="border:1px solid #ccd0d4;">
        <canvas id="myCanvas"></canvas>
    </div>

    <div class="map-controls">
        <button id="zoomInButton">+</button>
        <button id="zoomOutButton">-</button>
    </div>

    <div class="cgm-add-point cgm-accordion">
        <button type="button" class="cgm-accordion-toggle" aria-expanded="false">
            <span class="cgm-accordion-toggle-label">Add point</span>
            <span class="cgm-accordion-toggle-icon" aria-hidden="true">&gt;</span>
        </button>

        <div class="cgm-accordion-panel is-collapsed">
            <?php
            $point = [
                'pointName' => 'point-' . $next_point_id,
                'pin_color' => $default_pin_color,
            ];
            $default_name = 'point-' . $next_point_id;
            $display_index = 0;
            $redirect_view = 'project';
            include CGM_PATH . 'admin/views/point-form.php';
            ?>
        </div>
    </div>

    <div class="cgm-map-points cgm-accordion" id="cgm-map-points">
        <button type="button" class="cgm-accordion-toggle" aria-expanded="true">
            <span class="cgm-accordion-toggle-label">Points on map</span>
            <span class="cgm-accordion-toggle-icon" aria-hidden="true">&gt;</span>
        </button>

        <div class="cgm-accordion-panel">
            <p class="cgm-empty-points-state <?php echo empty( $points ) ? '' : 'is-hidden'; ?>">No points saved yet.</p>

            <div class="cgm-point-grid">
                <?php foreach ( $points as $index => $point ) : ?>
                    <div class="cgm-point-card" id="cgm-point-card-<?php echo esc_attr( (int) $point['id'] ); ?>" data-point-id="<?php echo esc_attr( (int) $point['id'] ); ?>">
                        <?php
                        $display_index = $index + 1;
                        $default_name = 'point-' . (int) $point['id'];
                        $redirect_view = 'project';
                        include CGM_PATH . 'admin/views/point-form.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
    #myCanvas { width: 100%; border: 1px solid #ccd0d4; display: block; }
    label, input { max-width: 100%; }
    .cgm-project-settings { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); margin-bottom: 20px; }
    .cgm-project-settings-card { background: #fff; border: 1px solid #dcdcde; border-radius: 6px; padding: 16px; }
    .cgm-field-label { display: inline-block; margin-bottom: 4px; }
    .cgm-alpha-color-field { display: inline-flex; flex-direction: column; gap: 10px; min-width: 220px; }
    .cgm-alpha-range-label { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .cgm-alpha-range-controls { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .cgm-add-point { margin-top: 24px; max-width: 500px; }
    .cgm-accordion-toggle { width: 100%; display: flex; align-items: center; justify-content: flex-start; gap: 15px; padding: 0; border: 0; background: transparent; font-size: 20px; font-weight: 600; text-align: left; cursor: pointer; }
    .cgm-accordion-toggle-icon { display: inline-flex; transition: transform .2s ease; }
    .cgm-accordion-toggle[aria-expanded="true"] .cgm-accordion-toggle-icon { transform: rotate(90deg); }
    .cgm-accordion-panel { margin-top: 16px; }
    .cgm-accordion-panel.is-collapsed { display: none; }
    .cgm-map-points { margin-top: 24px; }
    .cgm-point-grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
    .cgm-point-card, .cgm-point-form { background: #fff; border: 1px solid #dcdcde; border-radius: 6px; padding: 16px; scroll-margin-top: 24px; transition: box-shadow .25s ease, border-color .25s ease; }
    .cgm-point-card.is-focused { border-color: #2271b1; box-shadow: 0 0 0 3px rgba(34, 113, 177, .16); }
    .cgm-point-form h3 { margin-top: 0; }
    .cgm-coordinates { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cgm-range-controls { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .cgm-option-switcher { display: flex; gap: 12px; margin: 18px 0 12px; align-items: center; }
    .cgm-option-tab { width: 42px; height: 42px; border: 1px solid #d0d6dc; border-radius: 999px; background: linear-gradient(180deg, #f7f8fa 0%, #eef1f4 100%); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; filter: grayscale(1); transition: filter .18s ease, transform .18s ease, border-color .18s ease; }
    .cgm-option-tab .dashicons { font-size: 20px; width: 20px; height: 20px; pointer-events: none; }
    .cgm-option-tab[data-option-type="url"].is-active { color: #1565c0; border-color: #1565c0; filter: grayscale(0); }
    .cgm-option-tab[data-option-type="image"].is-active { color: #2e7d32; border-color: #2e7d32; filter: grayscale(0); }
    .cgm-option-tab[data-option-type="audio"].is-active { color: #ef6c00; border-color: #ef6c00; filter: grayscale(0); }
    .cgm-option-tab.is-active { transform: translateY(-1px); }
    .cgm-option-panel { display: none; margin: 10px 0 16px; }
    .cgm-option-panel.is-active { display: block; }
    .cgm-media-field { margin: 0; padding: 12px; background: #f6f7f7; border-radius: 4px; }
    .cgm-image-preview.is-empty, .cgm-audio-preview.is-empty { display: none; }
    .cgm-audio-preview audio { width: 100%; max-width: 260px; }
    .is-hidden { display: none !important; }
</style>
