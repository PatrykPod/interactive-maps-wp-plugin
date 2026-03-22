<div class="wrap">
    <h1>Custom GPS Maps</h1>

    <div class="cgm-project-layout" style="display:grid;gap:24px;grid-template-columns:minmax(320px,420px) 1fr;">
        <div class="cgm-project-create" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px;">
            <h2>Create project</h2>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="custom_gps_maps_add_map">

                <p>
                    <label>
                        Project name<br>
                        <input type="text" name="map_name" class="regular-text" placeholder="Museum floor plan">
                    </label>
                </p>

                <p>
                    <input type="submit" class="button button-primary" value="Create project">
                </p>
            </form>
        </div>

        <div class="cgm-project-table" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px;">
            <h2>Projects</h2>

            <?php if ( empty( $maps ) ) : ?>
                <p>No projects yet.</p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Shortcode</th>
                            <th>Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $maps as $map ) : ?>
                            <tr>
                                <td><?php echo esc_html( $map['name'] ); ?></td>
                                <td><code><?php echo esc_html( $map['shortcode'] ); ?></code></td>
                                <td><a class="button" href="<?php echo esc_url( CGM_Helper::get_project_view_url( $map['id'] ) ); ?>">Open project</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
