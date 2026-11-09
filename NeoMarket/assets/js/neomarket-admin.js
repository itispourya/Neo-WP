jQuery(document).ready(function($) {
    // Media Uploader for Gallery
    var frame;
    $('#neomarket-add-gallery').on('click', function(e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: neomarket_admin.gallery_title,
            button: {
                text: neomarket_admin.gallery_button
            },
            multiple: true
        });

        frame.on('select', function() {
            var selections = frame.state().get('selection');
            var galleryIds = $('#neomarket_product_gallery').val() ? $('#neomarket_product_gallery').val().split(',') : [];

            selections.map(function(attachment) {
                attachment = attachment.toJSON();
                if (galleryIds.indexOf(attachment.id.toString()) === -1) {
                    galleryIds.push(attachment.id.toString());
                    $('#neomarket-gallery-list').append(
                        '<li data-id="' + attachment.id + '" style="position: relative;">' +
                        '<img src="' + attachment.sizes.thumbnail.url + '" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ddd;">' +
                        '<a href="#" class="remove-gallery-img" style="position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 15px; height: 15px; text-align: center; line-height: 15px; font-size: 10px; text-decoration: none;">&times;</a>' +
                        '</li>'
                    );
                }
            });

            $('#neomarket_product_gallery').val(galleryIds.join(','));
        });

        frame.open();
    });

    // Remove Gallery Image
    $(document).on('click', '.remove-gallery-img', function(e) {
        e.preventDefault();
        var li = $(this).closest('li');
        var id = li.data('id').toString();
        var galleryIds = $('#neomarket_product_gallery').val().split(',');
        
        galleryIds = galleryIds.filter(function(val) {
            return val !== id;
        });

        $('#neomarket_product_gallery').val(galleryIds.join(','));
        li.remove();
    });

    // Specifications Repeater
    $('#add-spec').on('click', function() {
        var html = '<div class="neomarket-spec-item">' +
                   '<input type="text" name="neomarket_spec_label[]" placeholder="Label (e.g. Color)"> ' +
                   '<input type="text" name="neomarket_spec_value[]" placeholder="Value (e.g. Red)"> ' +
                   '<button type="button" class="button remove-spec">Remove</button>' +
                   '</div>';
        $('#neomarket-specs-repeater').append(html);
    });

    $(document).on('click', '.remove-spec', function() {
        $(this).closest('.neomarket-spec-item').remove();
    });
});
