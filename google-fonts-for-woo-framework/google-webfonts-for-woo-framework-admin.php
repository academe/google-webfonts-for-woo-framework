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

    public $current_google_fonts = null;

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

        // Action for displaying admin notices.
        add_action('admin_notices', array($this, 'display_admin_notice'));

        parent::init();
    }

    /**
     * Display an admin notice if there is one.
     */

    public function display_admin_notice() {
        if ($this->admin_notice == '') return;
        echo '<div class="updated">';
        echo $this->admin_notice;
        echo '</div>';
    }

    /**
     * Add "options_page" will go under the settings menu as a sub-menu.
     */

    public function admin_menu()
    {
        add_options_page(
            __('Google Webfonts for Woo Framework Options'),
            __('Google Webfonts for Woo Framework'),
            'manage_options',
            self::settings_page_slug,
            array($this, 'plugin_options')
        );
    }

    /**
     * Rendering the main admin "settings" page.
     */

    public function plugin_options()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get the current data from Google.
        $this->current_google_fonts = $this->getGoogleFontData();

        echo '<div class="wrap">';
        screen_icon();
        echo '<h2>' . __('Google Webfonts for Woo Framework Options') . '</h2>';

        if (empty($this->current_google_fonts)) {
            $this->current_google_fonts = $this->getFallbackFontData();
            $this->admin_notice = __('Cannot access Google Webfonts. Check your API key. Using fallback font list instead.');
            $this->display_admin_notice();
            $this->admin_message = 'wooo';
        }

        echo '<form method="post" action="options.php" id="' . self::settings_form_id . '">';

        echo '<table class="form-table">';
        settings_fields(self::settings_group_name);
        do_settings_sections(self::settings_page);
        echo '</table>';

        submit_button();

        echo '</form>';

        echo '</div>';
    }

    /**
     * Registering all the parts of the settings page.
     */

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
            __('Framework fonts built-in'),
            array($this, self::settings_field_old_fonts . '_field'),
            self::settings_page,
            self::settings_section_id
        );

        // List of added fonts (read-only).
        add_settings_field(
            self::settings_field_new_fonts,
            __('New fonts available and used'),
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

    /**
     * Render summary text/introduction to the main section.
     */

    public function plugin_main_section_text()
    {
        echo '<p>' . __('Google Webfonts for WooThemes Woo Framework. All fonts listed here are available to the theme.') . '</p>';
        echo '<p>' . __('Fonts shown selected here have been used in the theme.') . '</p>';
        echo '<p>' . __('To preview any fonts, select the fonts from either list and press the preview button..') . '</p>';
    }

    //
    // Display the input fields.
    //

    /**
     * Render the Google API Key input field.
     */

    public function google_api_key_field() {
        $option = get_option(self::settings_field_api_key, '');
        echo "<input id='" . self::settings_field_api_key . "' name='" . self::settings_field_api_key . "' size='80' type='text' value='{$option}' />";
    }

    /**
     * Expand a list of variants stored by the Woo Framework into a more friendly list.
     */

    public function expand_woo_variants($variants)
    {
        $variants_arr = explode(',', $variants);

        return implode(', ', preg_replace(
            array('/:/', '/bi$/', '/r$/', '/i$/', '/b$/'),
            array('', 'bold-italic', 'regular', 'italic', 'bold'),
            $variants_arr
        ));
    }

    /**
     * Expand an array of variants from Google into a more friendly list.
     */

    public function expand_google_variants($variants)
    {
        return str_replace(
            array('i', '200', '400', '700', '800', '900'),
            array(' italic', 'light', 'regular', 'bold', 'extra-bold', 'ultra-bold'),
            $variants
        );
    }

    /**
     * Render the list of original framework fonts.
     */

    public function old_fonts_field() {
        $used_fonts = $this->fonts_used_in_theme();

        if (empty($this->old_fonts)) {
            _e('No framework fonts found');
        } else {
            echo '<select name="' . self::settings_field_old_fonts . '" multiple="multiple" size="10" class="' . self::settings_field_select_class . '">';

            $i = 1;
            foreach($this->old_fonts as $font) {
                $selected = (isset($used_fonts[$font['name']])) ? ' selected="selected"' : '';

                echo '<option value="'. $font['name'] .'"' . $selected . '>' 
                    . $font['name'] 
                    . (!empty($font['variant']) ? ' (' . $this->expand_woo_variants($font['variant']) . ')' : '')
                    . '</option>';
            }

            echo '</select> (' . count($this->old_fonts) . ')';
        }
    }

    /**
     * Render the list of new fonts this plugin makes available.
     * FIXME: HTML encode the font name.
     */

    public function new_fonts_field() {
        $used_fonts = $this->fonts_used_in_theme();

        echo '<select name="' . self::settings_field_new_fonts . '" multiple="multiple" size="10" class="' . self::settings_field_select_class . '">';

        $i = 1;
        foreach($this->current_google_fonts as $name => $variants) {
            $selected = (isset($used_fonts[$name])) ? ' selected="selected"' : '';

            echo '<option value="'. $name .'"' . $selected . '>' 
                . htmlspecialchars($name)
                . (!empty($variants) ? ' (' . implode(', ', $this->expand_google_variants($variants)) . ')' : '')
                . '</option>';
        }

        echo '</select> (' . count($this->current_google_fonts) . ')';
    }

    /**
     * Preview any selected fonts.
     */

    public function preview_fonts_field() {
        //
        // Here provide the preview.
        //

        echo '<p><input type="submit" id="preview-fonts" value="' . __('Preview Fonts') . '" onClick="jQuery().gwfwFontPreview({clear: true}); return false;" /></p>';

        wp_enqueue_script(
            'preview-fonts',
            plugins_url('google-fonts-for-woo-framework/preview-fonts.js'),
            false, // dependances TODO - this depends on jQuery
            false,
            false 
        );

        // This is where the previews will be placed.
        echo '<div id="gwfw-font-previews"></div>';
    }


    /**
     * Validate the submitted fields.
     */

    public function google_api_key_validate($input) {
        // Make sure it is a URL-safe string.
        if ($input != rawurlencode($input)) {
            add_settings_error(self::settings_field_api_key, 'texterror', __('API key contains invalid characters'), 'error');
        } else {
            // If valid, then replace the current cache with new Google fonts.
            $fonts = $this->getGoogleFonts();
            if ( ! empty($fonts)) {
                set_transient($this->trans_cache_name, $fonts);
            }
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

    /**
     * Get the full list of fonts from Google.
     * Return as an array: font-family => array(variants)
     * This is the same format as returned by getFallbackFonts()
     */

    public function getGoogleFontData()
    {
        $google_api_key = get_option('google_api_key', '');
        $font_list = false;

        // If not API key is set yet, then abort.
        if (empty($google_api_key)) return $font_list;

        // The API key should be URL-safe.
        // We need to ensure it is when setting it in the admin page.
        $api_data = wp_remote_get($this->api_url . $google_api_key);

        // If the fetch failed, then report it to the admin.
        if (is_wp_error($api_data)) {
            $error_message = $api_data->get_error_message();
            $this->admin_notice = "<p>Error fetching Google font: $error_message</p>";
            return $font_list;
        }

        $response = $api_data['response'];

        if (200 === $response['code']) {
            $font_list = json_decode($api_data['body'], true);
        }

        if (empty($font_list) || !is_array($font_list)) return $font_list;

        $fonts = array();
        foreach($font_list['items'] as $font) {
            $variants = $font['variants'];

            // Normalise the weights. Make sure every variant has
            // an explicit weight. This makes it easier to process later.
            // Also abreviate "italic" to "i".
            //
            // Font weights are:
            // 100 ultra-light
            // 200 light
            // 300 book
            // 400 normal
            // 500 medium
            // 600 semi-bold
            // 700 bold
            // 800 extra-bold
            // 900 ultra-bold

            foreach($variants as $vkey => $vname) {
                if ($vname == 'regular') $variants[$vkey] = '400';
                if ($vname == 'italic') {
                    $vname = '400italic';
                    $variants[$vkey] = $vname;
                }
                if (strpos($vname, 'italic') !== false) {
                    $variants[$vkey] = str_replace('italic', 'i', $vname);
                }
            }

            $fonts[$font['family']] = $variants;
        }

        ksort($fonts);

        return $fonts;
    }

    /**
     * Get the full list of fonts from Google.
     * TODO: allow the variants to be restricted to a smaller set than is available.
     * For example, Ultrabold (900) is not available in any Canvas, so there is no point loading it.
     */

    public function getGoogleFonts()
    {
        $font_list = $this->getGoogleFontData();

        if (empty($font_list)) return $font_list;

        // We want to go through the list and abbreviate it.
        // Some of the variant names are abreviated. We could go further, with the weights.
        // For example, 700 == bold == b == 7
        $fonts = array();
        foreach($font_list as $family => $variants) {
            if ( ! empty($variants)) {
                // Woo Framework expects the leading ":" to be included in the variant list. It does
                // not insert it at the point of use.
                $variant = ':' . implode(',', $this->abbreviateVariants($variants));
            } else {
                $variant = '';
            }

            $fonts[] = array(
                'name' => $family,
                'variant' => $variant,
            );
        }

        return $fonts;
    }
}

