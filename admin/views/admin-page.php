<?php
$image_id  = CGM_Helper::get_map_image_id();
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
$dimensions = CGM_Helper::get_image_dimensions();
?>

<div class="wrap">
    <h1>Custom GPS Maps</h1>

    <div class="controller" style="display:flex;align-items:center;gap:35px;">

        <div class="left">

            <h2>Map image</h2>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="custom_gps_maps_save_image">

                <input type="hidden"
                       id="custom-gps-map-image-id"
                       name="custom_gps_maps_image_id"
                       value="<?php echo esc_attr($image_id); ?>">

                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width:100%;height:auto;">
                <?php endif; ?>

                <button type="button" class="button" id="custom-gps-map-image-select">
                    <?php echo $image_id ? 'Change map image' : 'Select map image'; ?>
                </button>

                <p>
                    <input type="submit" class="button button-primary" value="Save map image">
                </p>
            </form>

        </div>


        <div class="right">

            <?php if ($dimensions): ?>
                <p class="description">
                    <strong>Coordinate system:</strong><br><br>
                    Width (X): <code>0 – <?php echo esc_html($dimensions['width']); ?></code><br>
                    Height (Y): <code>0 – <?php echo esc_html($dimensions['height']); ?></code>
                </p>
            <?php else: ?>
                <p class="description">
                    ⚠️ Map image not found — coordinate ranges unavailable.
                </p>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="custom_gps_maps_add_point">

                <label>
                    Name:
                    <input type="text" name="point_name">
                </label><br>

                <label>
                    X:
                    <input type="number" name="x">
                </label><br>

                <label>
                    Y:
                    <input type="number" name="y">
                </label><br>

                <input type="submit" value="Add Point">
            </form>

        </div>
    </div>


    <div class="list">
        <h2>Saved points</h2>

        <ul>
            <?php foreach ($points as $point): ?>
                <li>
                    <strong><?php echo esc_html($point['pointName']); ?></strong><br>
                    x: <?php echo esc_html($point['x']); ?><br>
                    y: <?php echo esc_html($point['y']); ?><br>
                    <a href="<?php echo esc_url(
                        admin_url('admin-post.php?action=custom_gps_maps_delete_point&id=' . $point['id'])
                    ); ?>">Delete</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

</div>