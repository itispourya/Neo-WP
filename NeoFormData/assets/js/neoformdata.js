jQuery(function ($) {
    if ($.fn.wpColorPicker) {
        $('.neo-color-field').wpColorPicker();
    }

    var fieldList = $('#neoformdata-field-list');
    var template = $('#neoformdata-field-template').html() || '';

    $('#neoformdata-add-field').on('click', function () {
        if (!fieldList.length || !template) {
            return;
        }

        var nextIndex = fieldList.children('.neo-field-builder').length;
        var markup = template.replace(/__INDEX__/g, nextIndex);
        fieldList.append(markup);
    });

    $(document).on('click', '.neo-remove-field', function () {
        $(this).closest('.neo-field-builder').remove();
    });
});
