jQuery(function ($) {

    let mapFrame;
    const mediaFrames = {};

    function initColorPickers(context) {
        if (!$.fn.wpColorPicker) {
            return;
        }

        const scope = context ? $(context) : $(document);
        scope.find('.cgm-alpha-color-field').each(function () {
            const field = $(this);
            const input = field.find('.cgm-color-picker').first();

            if (input.hasClass('wp-color-picker')) {
                syncAlphaColorField(field);
                return;
            }

            input.wpColorPicker({
                change: function () {
                    syncAlphaColorField(field);
                },
                clear: function () {
                    window.setTimeout(function () {
                        syncAlphaColorField(field);
                    }, 0);
                }
            });

            syncAlphaColorField(field);
        });
    }

    function syncAlphaColorField(field) {
        const hiddenInput = field.find('.cgm-alpha-color-value').first();
        const colorInput = field.find('.cgm-color-picker').first();
        const alphaInput = field.find('.cgm-alpha-range-input').first();
        const alphaValue = field.find('.cgm-alpha-range-value').first();
        const color = (colorInput.val() || colorInput.data('default-color') || '#ff0000').toString().trim();
        const alphaPercent = Number.parseInt(alphaInput.val(), 10);
        const safeAlphaPercent = Number.isFinite(alphaPercent) ? Math.max(0, Math.min(100, alphaPercent)) : 100;
        const alpha = safeAlphaPercent / 100;
        const hex = /^#(?:[0-9a-f]{3}){1,2}$/i.test(color) ? color : '#ff0000';

        alphaValue.text(`${safeAlphaPercent}%`);

        if (safeAlphaPercent >= 100) {
            hiddenInput.val(hex);
            return;
        }

        const rgb = hexToRgb(hex);

        if (!rgb) {
            hiddenInput.val('#ff0000');
            return;
        }

        hiddenInput.val(`rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha.toFixed(2).replace(/0+$/, '').replace(/\.$/, '')})`);
    }

    function hexToRgb(hex) {
        const normalized = (hex || '').replace('#', '').trim();
        const fullHex = normalized.length === 3
            ? normalized.split('').map(char => char + char).join('')
            : normalized;

        if (!/^[0-9a-f]{6}$/i.test(fullHex)) {
            return null;
        }

        return {
            r: Number.parseInt(fullHex.slice(0, 2), 16),
            g: Number.parseInt(fullHex.slice(2, 4), 16),
            b: Number.parseInt(fullHex.slice(4, 6), 16)
        };
    }

    function updatePinColorVisibility(form) {
        const pinIconField = form.find('.cgm-pin-icon-field');
        const pinIconInput = pinIconField.find('input[name="point_pin_icon_id"]');
        const rawValue = (pinIconInput.val() || '').toString().trim();
        const pinIconId = Number.parseInt(rawValue, 10);
        const hasPinIcon = Number.isFinite(pinIconId) && pinIconId > 0;

        pinIconField.find('.cgm-point-color-field').toggleClass('is-hidden', hasPinIcon);
        // pinIconField.find('.cgm-pin-icon-opacity-field').toggleClass('is-hidden', !hasPinIcon);
    }

    initColorPickers(document);

    $('#custom-gps-map-image-select').on('click', function (e) {
        e.preventDefault();

        if (mapFrame) {
            mapFrame.open();
            return;
        }

        mapFrame = wp.media({
            title: 'Select map image',
            button: { text: 'Use this image' },
            library: { type: 'image' },
            multiple: false
        });

        mapFrame.on('select', function () {
            const attachment = mapFrame.state().get('selection').first().toJSON();
            $('#custom-gps-map-image-id').val(attachment.id);
        });

        mapFrame.open();
    });

    function renderMediaPreview(container, type, attachment) {
        if (!container.length) {
            return;
        }

        if (!attachment) {
            container.addClass('is-empty').empty();
            return;
        }

        if (type === 'image') {
            container
                .removeClass('is-empty')
                .html(`<img src="${attachment.url}" alt="" style="max-width:140px;height:auto;">`);
            return;
        }

        container
            .removeClass('is-empty')
            .html(`<audio controls preload="none" src="${attachment.url}"></audio>`);
    }

    function getMediaButtonLabel(button) {
        return button.data('media-label') || (button.data('media-type') === 'audio' ? 'audio' : 'image');
    }

    function clearInactiveValues(form, activeType) {
        if (activeType !== 'url') {
            form.find('input[name="point_url"]').val('');
        }

        if (activeType !== 'image') {
            form.find('input[name="point_image_id"]').val('');
            renderMediaPreview(form.find('.cgm-image-preview'), 'image', null);
            form.find('[data-option-panel="image"] .cgm-clear-media').addClass('is-hidden');
            form.find('[data-option-panel="image"] .cgm-media-button').text('Select image');
        }

        if (activeType !== 'audio') {
            form.find('input[name="point_audio_id"]').val('');
            renderMediaPreview(form.find('.cgm-audio-preview'), 'audio', null);
            form.find('[data-option-panel="audio"] .cgm-clear-media').addClass('is-hidden');
            form.find('[data-option-panel="audio"] .cgm-media-button').text('Select audio');
        }
    }

    function setActiveOption(form, type, shouldClearOthers) {
        form.find('.cgm-content-type-input').val(type);
        form.attr('data-active-type', type);

        form.find('.cgm-option-tab').each(function () {
            const tab = $(this);
            const isActive = tab.data('option-type') === type;
            tab.toggleClass('is-active', isActive);
            tab.attr('aria-pressed', isActive ? 'true' : 'false');
        });

        form.find('.cgm-option-panel').each(function () {
            const panel = $(this);
            panel.toggleClass('is-active', panel.data('option-panel') === type);
        });

        if (shouldClearOthers) {
            clearInactiveValues(form, type);
        }
    }

    $(document).on('click', '.cgm-option-tab, .cgm-option-tab .dashicons, .cgm-option-tab .screen-reader-text', function (e) {
        e.preventDefault();
        const button = $(this).closest('.cgm-option-tab');
        const form = button.closest('.cgm-point-form');
        setActiveOption(form, button.data('option-type'), true);
    });

    $(document).on('focus', '.cgm-url-input', function () {
        const form = $(this).closest('.cgm-point-form');
        setActiveOption(form, 'url', false);
    });

    $(document).on('click', '.cgm-media-button', function (e) {
        e.preventDefault();

        const button = $(this);
        const field = button.closest('.cgm-media-field');
        const form = button.closest('.cgm-point-form');
        const panel = button.closest('.cgm-option-panel');
        const input = field.find('.cgm-media-input');
        const type = button.data('media-type');

        if (panel.length) {
            setActiveOption(form, type, true);
        }

        if (!mediaFrames[type]) {
            mediaFrames[type] = wp.media({
                title: type === 'image' ? 'Select point image' : 'Select point audio',
                button: { text: type === 'image' ? 'Use this image' : 'Use this audio' },
                library: { type: type },
                multiple: false
            });
        }

        mediaFrames[type].off('select');
        mediaFrames[type].on('select', function () {
            const attachment = mediaFrames[type].state().get('selection').first().toJSON();
            const label = getMediaButtonLabel(button);

            input.val(attachment.id);
            renderMediaPreview(field.find(type === 'image' ? '.cgm-image-preview' : '.cgm-audio-preview'), type, attachment);
            field.find('.cgm-clear-media').removeClass('is-hidden');
            button.text(`Replace ${label}`);
            updatePinColorVisibility(form);
        });

        mediaFrames[type].open();
    });

    $(document).on('click', '.cgm-clear-media', function (e) {
        e.preventDefault();

        const button = $(this);
        const field = button.closest('.cgm-media-field');
        const form = button.closest('.cgm-point-form');
        const panel = button.closest('.cgm-option-panel');
        const mediaButton = field.find('.cgm-media-button');
        const type = mediaButton.data('media-type');
        const label = getMediaButtonLabel(mediaButton);

        field.find('.cgm-media-input').val('');
        renderMediaPreview(field.find(type === 'image' ? '.cgm-image-preview' : '.cgm-audio-preview'), type, null);
        button.addClass('is-hidden');
        mediaButton.text(`Select ${label}`);
        updatePinColorVisibility(form);

        if (panel.length) {
            setActiveOption(form, 'url', true);
        }
    });

    $('.cgm-point-form').each(function () {
        const form = $(this);
        setActiveOption(form, form.find('.cgm-content-type-input').val() || 'url', false);
        updatePinColorVisibility(form);
    });

    $(document).on('input change', '.cgm-range-input', function () {
        const input = $(this);
        input.closest('.cgm-range-field').find('.cgm-range-value').text(`${input.val()}%`);
    });

    $(document).on('input change', '.cgm-alpha-range-input', function () {
        syncAlphaColorField($(this).closest('.cgm-alpha-color-field'));
    });

    $(document).on('cgm:point-form-added', function (event, formElement) {
        initColorPickers(formElement);
        updatePinColorVisibility($(formElement));
    });

    function syncAccordion(toggle, panel, isExpanded) {
        toggle.attr('aria-expanded', isExpanded ? 'true' : 'false');
        panel.toggleClass('is-collapsed', !isExpanded);
    }

    $(document).on('click', '.cgm-accordion-toggle', function (e) {
        e.preventDefault();

        const toggle = $(this);
        const panel = toggle.closest('.cgm-accordion').find('.cgm-accordion-panel').first();
        const isExpanded = toggle.attr('aria-expanded') === 'true';

        if (isExpanded) {
            toggle.attr('aria-expanded', 'false');
            panel.stop(true, true).slideUp(180, function () {
                panel.addClass('is-collapsed');
            });
            return;
        }

        panel.removeClass('is-collapsed').hide();
        toggle.attr('aria-expanded', 'true');
        panel.stop(true, true).slideDown(180);
    });

    $('.cgm-accordion').each(function () {
        const container = $(this);
        const toggle = container.find('.cgm-accordion-toggle').first();
        const panel = container.find('.cgm-accordion-panel').first();
        const isExpanded = toggle.attr('aria-expanded') === 'true';

        syncAccordion(toggle, panel, isExpanded);
    });
});
