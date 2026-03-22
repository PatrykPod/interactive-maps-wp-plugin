<?php
$redirect_page = 'custom-gps-maps-map-view';
?>

<div class="wrap">
    <h1>Map preview</h1>

    <p class="description">Double-click the map to create a new point, then use the forms below to attach either a URL, image, or audio file.</p>

    <div style="border:1px solid #ccd0d4;">
        <canvas id="myCanvas"></canvas>
    </div>

    <div class="map-controls">
        <button id="zoomInButton">+</button>
        <button id="zoomOutButton">-</button>
    </div>

    <div class="cgm-map-points" id="cgm-map-points">
        <h2>Points on map</h2>

        <p class="cgm-empty-points-state <?php echo empty( $points ) ? '' : 'is-hidden'; ?>">No points saved yet.</p>

        <div class="cgm-point-grid">
            <?php foreach ( $points as $index => $point ) : ?>
                <div class="cgm-point-card" id="cgm-point-card-<?php echo esc_attr( (int) $point['id'] ); ?>" data-point-id="<?php echo esc_attr( (int) $point['id'] ); ?>">
                    <?php
                    $display_index = $index + 1;
                    $default_name = 'point-' . (int) $point['id'];
                    include CGM_PATH . 'admin/views/point-form.php';
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
    #myCanvas {
        width: 100%;
        border: 1px solid #ccd0d4;
        display: block;
    }

    .cgm-map-points {
        margin-top: 24px;
    }

    .cgm-point-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    }

    .cgm-point-card,
    .cgm-point-form {
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 6px;
        padding: 16px;
        scroll-margin-top: 24px;
        transition: box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .cgm-point-card.is-focused {
        border-color: #2271b1;
        box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.16);
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
