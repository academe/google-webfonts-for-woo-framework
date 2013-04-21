<?php

/**
 * This script contains an admin-section extended version of the plugin.
 */

class GoogleWebfontsForWooFrameworkAdmin extends GoogleWebfontsForWooFramework
{
    // Some constants to help make sense of the admin page structure.
    const settings_group_name = 'gwfc-group';
    const settings_page_slug = 'gw-for-wooframework';
    // CHECKME: the 'page' should "match the page slug" according to some codex documentation,
    // but is different in many code examples.
    const settings_page = 'gwfc_main_section';
    const settings_section_id = 'gwfc_main';

    // Field names.
    const settings_form_id = 'gwfc_settings_form';
    const settings_field_api_key = 'google_api_key';
    const settings_field_new_fonts = 'new_fonts';
    const settings_field_old_fonts = 'old_fonts';
    const settings_field_preview = 'preview_fonts';
    // Give all font selector select form items a class so we can find them.
    const settings_field_select_class = 'font-selector';

    public function init()
    {
        // Add the missing fonts in the admin page.
        add_action('admin_head', array($this, 'action_add_fonts'), 20);

        if (is_admin()) {
            // Add the admin menu.
            add_action('admin_menu', array($this, 'admin_menu'));

            // Register the settings.
            add_action('admin_init', array($this, 'register_settings'));
        }

        parent::init();
    }

    public function admin_menu()
    {
        // And "options_page" will go under the settings menu as a sub-menu.
        add_options_page(
            __('Google Webfonts for Woo Framework Options'),
            __('Google Webfonts for Woo Framework'),
            'manage_options',
            self::settings_page_slug,
            array($this, 'plugin_options')
        );
    }

    public function plugin_options()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        echo '<div class="wrap">';
        screen_icon();
        echo '<h2>' . __('Google Webfonts for Woo Framework Options') . '</h2>';

        echo '<form method="post" action="options.php" id="' . self::settings_form_id . '">';

        echo '<table class="form-table">';
        settings_fields(self::settings_group_name);
        do_settings_sections(self::settings_page);
        echo '</table>';

        submit_button();

        echo '</form>';

        echo '</div>';
    }

    public function register_settings()
    {
        // Register the settings.

        register_setting(
            self::settings_group_name, 
            self::settings_field_api_key,
            array($this, self::settings_field_api_key . '_validate')
        );

        // Register a section in the page.

        add_settings_section(
            self::settings_section_id, 
            __('Main Settings'), 
            array($this, 'plugin_main_section_text'),
            self::settings_page
        );

        // Add fields to the section.

        // The API key.
        add_settings_field(
            self::settings_field_api_key,
            __('Google Developer API Key'),
            array($this, self::settings_field_api_key . '_field'),
            self::settings_page,
            self::settings_section_id // section
        );

        // List of added fonts (read-only).
        add_settings_field(
            self::settings_field_old_fonts,
            __('Framework fonts (view only)'),
            array($this, self::settings_field_old_fonts . '_field'),
            self::settings_page,
            self::settings_section_id
        );

        // List of added fonts (read-only).
        add_settings_field(
            self::settings_field_new_fonts,
            __('New fonts introduced (view only)'),
            array($this, self::settings_field_new_fonts . '_field'),
            self::settings_page,
            self::settings_section_id
        );

        // Font preview section.
        add_settings_field(
            self::settings_field_preview,
            __('Preview the selected fonts'),
            array($this, self::settings_field_preview . '_field'),
            self::settings_page,
            self::settings_section_id
        );

    }

    // Summary text/introduction to the main section.

    public function plugin_main_section_text()
    {
        echo '<p>' . __('Google Webfonts for WooThemes Woo Framework. Fonts show selected have been used within the theme.') . '</p>';
    }

    // Display the input fields.

    // The Google API Key input field.
    public function google_api_key_field() {
        $option = get_option(self::settings_field_api_key, '');
        echo "<input id='" . self::settings_field_api_key . "' name='" . self::settings_field_api_key . "' size='80' type='text' value='{$option}' />";
    }

    // Display the list of original framework fonts.
    public function old_fonts_field() {
        $used_fonts = $this->fonts_used_in_theme();

        if (empty($this->old_fonts)) {
            _e('No framework fonts found');
        } else {
            echo '<select name="' . self::settings_field_old_fonts . '" multiple="multiple" size="10" class="' . self::settings_field_select_class . '">';

            $i = 1;
            foreach($this->old_fonts as $font) {
                $selected = (isset($used_fonts[$font['name']])) ? ' selected="selected"' : '';
                echo '<option value="'. $font['name'] .'"' . $selected . '>' .$font['name']. '</option>';
            }

            echo '</select> (' . count($this->old_fonts) . ')';
        }
    }

    // Display the list of new fonts this plugin makes available.
    // FIME: HTML encode the font name.
    public function new_fonts_field() {
        $used_fonts = $this->fonts_used_in_theme();

        if (empty($this->new_fonts)) {
            _e('No new fonts found');
        } else {
            echo '<select name="' . self::settings_field_new_fonts . '" multiple="multiple" size="10" class="' . self::settings_field_select_class . '">';

            $i = 1;
            foreach($this->new_fonts as $font) {
                $selected = (isset($used_fonts[$font['name']])) ? ' selected="selected"' : '';
                echo '<option value="'. $font['name'] .'"' . $selected . '>' . $font['name'] . '</option>';
            }

            echo '</select> (' . count($this->new_fonts) . ')';
        }
    }

    // Preview any selected fonts.
    public function preview_fonts_field() {
        //
        // Here provide the preview.
        //

        echo '<p><input type="submit" id="preview-fonts" value="' . __('Preview Fonts') . '" onClick="jQuery().gwfwFontPreview({clear: true}); return false;" /></p>';

        // form id = self::settings_form_id

        echo '<script type="text/javascript">';
        echo 'jQuery(document).ready(function($){$("#gwfw-font-previews").html("This is Hello World by JQuery");});';

        // TODO: this to go into a separate file.
        echo <<<JSEND
(function($){
jQuery.fn.gwfwFontPreview = (function(options) {
    var settings = jQuery.extend({
        clear: false,
        form_id: 'gwfc_settings_form',
        font_selector_class: 'font-selector',
        preview_text: 'The quick brown fox jumps over the lazy dog',
        google_bas_url: 'http://fonts.googleapis.com/css?family='
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
                + '<p style="font-family:' + font_list[i] + '; font-size: 36pt; line-height: 36pt;">' + settings.preview_text + '</p>'
            );

            // Since the Woo framework does not load the fonts in the admin section until needed, 
            // we will load them through AJAX.
            $('head').append('<link href="' + settings.google_bas_url + font_list[i] + '" rel="stylesheet" type="text/css">');
        }
    }

    return this;
});
}) (jQuery);
JSEND;

        echo '</script>';

        // This is where the previews will be placed.
        echo '<div id="gwfw-font-previews"></div>';
    }


    // Validate the submitted fields.

    public function google_api_key_validate($input) {
        // Make sure it is a URL-safe string.
        if ($input != rawurlencode($input)) {
            add_settings_error(self::settings_field_api_key, 'texterror', __('API key contains invalid characters'), 'error');
        } else {
            // If valid, then discard the current fonts cache, so a fresh fetch is
            // done with the new key.
            delete_transient($this->trans_cache_name);
        }

        return $input;
    }

    /**
     * Get a list of all the fonts used in the theme at present.
     * TODO: check out global $woo_used_google_fonts first.
     */

    public function fonts_used_in_theme()
    {
        global $google_fonts;
        global $woo_options;
        static $fonts = null;

        // If we have done the search already, then returned the cached list.
        if (is_array($fonts)) return $fonts;

        // The font list we will return.
        $fonts = array();

        // List of font names we find in the options of the theme.
        $option_fonts = array();

        // Go through the options in the theme.
        if (!empty($woo_options)) {
            foreach ($woo_options as $option) {
                // Check if option has "face" in array.
                if (is_array($option) && isset($option['face'])) {
                    if (!isset($option_fonts[$option['face']])) {
                        $option_fonts[$option['face']] = $option['face'];
                    }
                }
            }

            // Now if we have found a list of font families used in the theme, check which
            // are available as Google fonts. We are only interested in those (for now, while
            // Google is the only source).
            if (!empty($option_fonts)) {
                foreach ($google_fonts as $font) {
                    if (isset($option_fonts[$font['name']]) && !isset($fonts[$font['name']])) {
                        $fonts[$font['name']] = $font['name'];
                    }
                }
            }
        }

        return $fonts;
    }

}

