(function () {
    if (typeof jQuery !== 'undefined') {
        jQuery(function ($) {
            if ($.fn.wpColorPicker) {
                $('.neo-color-field').wpColorPicker();
            }

            $('.neo-media-button').on('click', function (event) {
                event.preventDefault();

                var targetSelector = $(this).data('target');
                var targetField = $(targetSelector);

                if (!targetField.length || typeof wp === 'undefined' || !wp.media) {
                    return;
                }

                var frame = wp.media({
                    title: 'Choose media',
                    button: {
                        text: 'Use this file'
                    },
                    multiple: false
                });

                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    targetField.val(attachment.url).trigger('change');
                });

                frame.open();
            });
        });
    }

    function hideOverlay() {
        var overlay = document.getElementById('neoloadland-overlay');

        if (!overlay) {
            return;
        }

        overlay.classList.add('is-hiding');

        window.setTimeout(function () {
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, (window.NeoLoadLandConfig && window.NeoLoadLandConfig.fadeDuration) || 650);
    }

    if (document.readyState === 'complete') {
        hideOverlay();
    } else {
        window.addEventListener('load', function () {
            window.setTimeout(hideOverlay, 120);
        });

        window.setTimeout(hideOverlay, 8000);
    }
})();
