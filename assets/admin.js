jQuery(function ($) {

    let frame;

    $('#custom-gps-map-image-select').on('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select map image',
            button: { text: 'Use this image' },
            library: { type: 'image' },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();

            $('#custom-gps-map-image-id').val(attachment.id);

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                $('#custom-gps-map-image-id').val(attachment.id);
            });
        });

        frame.open();
    });
});
