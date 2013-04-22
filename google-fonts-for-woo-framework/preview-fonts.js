(function($){
jQuery.fn.gwfwFontPreview = (function(options) {
    var settings = jQuery.extend({
        clear: false,
        form_id: 'gwfc_settings_form',
        font_selector_class: 'font-selector',
        preview_text: 'The quick brown fox jumps over the lazy dog',
        google_base_url: 'http://fonts.googleapis.com/css?family='
    }, options);

    // If 'clear' is set, then remove any fonts previewed so far.
    if (settings.clear) {
        $('#gwfw-font-previews').html('');
    }

    // Get a list of fonts that are selected.
    // TODO: pass the form ID in as a parameter.
    var font_list = [];

    // For each select element that contains a list of fonts (identified by the form id
    // and the class name we have given them), pull out
    // all the selected fonts so we have a full list.
    $('#' + settings.form_id + ' select.' + settings.font_selector_class + ' :selected')
        .each(function(i){
            font_list.push($(this).val());
        });
    ;

    // TODO: sort this list into reverse value order, since we have combined
    // font names from several different form elements.

    // If we have any selected fonts, then loop through them to create the previews.
    if (font_list.length > 0) {
        for(var i = 0; i < font_list.length; i++) {
            // Display the preview text.
            $('#gwfw-font-previews').prepend(
                '<p style="font-weight: bold">' + font_list[i] + '</p>'
                + '<p style="font-family:\'' + font_name + '\'; font-size: 36pt; line-height: 36pt;">' + settings.preview_text + '</p>'
            );

            // Since the Woo framework does not load the fonts in the admin section until needed, 
            // we will load them through AJAX.
            $('head').append('<link href="' + settings.google_base_url + escape(font_list[i]) + '" rel="stylesheet" type="text/css">');
        }
    }

    return this;
});
}) (jQuery);