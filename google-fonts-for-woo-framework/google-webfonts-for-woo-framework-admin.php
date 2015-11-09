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
    const settings_field_api_key = 'googleApiKey';
    const settings_field_font_subset = 'fontSubset';
    const settings_field_font_weight = 'fontWeight';
    const settings_option_api_key = 'gwfc_google_api_key';
    const settings_option_font_subset = 'gwfc_google_webfont_subset';
    const settings_option_font_weight = 'gwfc_google_webfont_weight';
    const settings_field_new_fonts = 'newFonts';
    const settings_field_old_fonts = 'oldFonts';
    const settings_field_preview = 'previewFonts';
    // Give all font selector select form items a class so we can find them.
    const settings_field_select_class = 'font-selector';

    public $current_google_fonts = null;

    // The common-use English translation of variant weights.
    // These may need to be translated.
    public $all_font_weights = array(
        '100' => 'Ultra-light (Google: Thin)',
        '200' => 'Light (Google: Extra-Light)',
        '300' => 'Thin (Google: Light)',
        '400' => 'Regular/Normal',
        '500' => 'Medium',
        '600' => 'Semi-Bold',
        '700' => 'Bold',
        '800' => 'Extra-Bold',
        '900' => 'Ultra-Bold',
    );

    // The list of subsets that are (or will be) available from Google.
    public $all_font_subsets = array(
        'latin'         => '* Latin',
        'latin-ext'     => '* Latin Extended',
        'greek'         => '* Greek',
        'greek-ext'     => '* Greek Extended',
        'cyrillic'      => '* Cyrillic',
        'cyrillic-ext'  => '* Cyrillic Extended',

        //'menu' => 'Menu',

        'arabic'        => '* Arabic',      // e.g. Lateef
        'bengali'       => '* Bengali',     // e.g. Hind Siliguri
        'devanagari'    => '* Devanagari',  // e.g. Noto Sans
        'devanagari'    => '* Gujarati',    // e.g. Hind Vadodara
        'hebrew'        => '* Hebrew',      // e.g. Tinos
        'hindi'         => 'Hindi',
        'khmer'         => '* Khmer',       // e.g. Suwannaphum
        'korean'        => 'Korean',
        'lao'           => 'Lao',
        'tamil'         => '* Tamil',       // e.g. Catamaran
        'telugu'        => '* Telugu',      // e.g. Mallanna
        'thai'          => '* Thai',        // e.g. Chonburi
        'vietnamese'    => '* Vietnamese',
    );

    public function init()
    {
        // Add the missing fonts in the admin page.
        add_action('admin_head', array($this, 'action_set_fonts'), 5);

        // Add the admin menu.
        add_action('admin_menu', array($this, 'admin_menu'));

        // Register the settings.
        add_action('admin_init', array($this, 'register_settings'));

        // Action for displaying admin notices.
        add_action('admin_notices', array($this, 'displayAdminNotice'));

        parent::init();
    }

    /**
     * Display an admin notice if there is one.
     */

    public function displayAdminNotice($message = null) {
        if ( ! isset($message)) $message = $this->admin_notice;
        if ($message == '') return;

        // Wrap in paragraphs if no tags found in the message.
        if (strpos('<', $message) === false) $message = '<p>' . $message . '</p>';

        echo '<div class="updated">';
        echo $message;
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

            // Display the admin notive inline.
            $this->displayAdminNotice(
                __(
                    'Cannot access the Google Webfonts API.'
                    . ' Check your API key.'
                    . ' The fallback font list is being used instead.'
                )
            );

            // Display additional diagnostics if available.
            if ( ! empty($this->admin_notice)) {
                $this->displayAdminNotice(
                    $this->admin_notice
                );
            }
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
            self::settings_option_api_key,
            array($this, self::settings_field_api_key . 'Validate')
        );

        register_setting(
            self::settings_group_name, 
            self::settings_option_font_subset,
            array($this, self::settings_field_font_subset . 'Validate')
        );

        register_setting(
            self::settings_group_name, 
            self::settings_option_font_weight,
            array($this, self::settings_field_font_weight . 'Validate')
        );

        // Register a section in the page.

        add_settings_section(
            self::settings_section_id, 
            __('Main Settings'), 
            array($this, 'pluginMainSectionText'),
            self::settings_page
        );

        // Add fields to the section.

        // The API key.
        add_settings_field(
            self::settings_field_api_key,
            __('Google Developer API Key'),
            array($this, self::settings_field_api_key . 'Field'),
            self::settings_page,
            self::settings_section_id // section
        );

        // The font subsets.
        add_settings_field(
            self::settings_field_font_subset,
            __('Requested font subsets'),
            array($this, self::settings_field_font_subset . 'Field'),
            self::settings_page,
            self::settings_section_id // section
        );

        // The font weights.
        add_settings_field(
            self::settings_field_font_weight,
            __('Requested font weights'),
            array($this, self::settings_field_font_weight . 'Field'),
            self::settings_page,
            self::settings_section_id // section
        );

        // List of added fonts (read-only).
        add_settings_field(
            self::settings_field_old_fonts,
            __('Framework fonts built-in'),
            array($this, self::settings_field_old_fonts . 'Field'),
            self::settings_page,
            self::settings_section_id
        );

        // List of added fonts (read-only).
        add_settings_field(
            self::settings_field_new_fonts,
            __('All Google Webfonts'),
            array($this, self::settings_field_new_fonts . 'Field'),
            self::settings_page,
            self::settings_section_id
        );

        // Font preview section.
        add_settings_field(
            self::settings_field_preview,
            __('Preview the selected fonts'),
            array($this, self::settings_field_preview . 'Field'),
            self::settings_page,
            self::settings_section_id
        );
    }

    /**
     * Render summary text/introduction to the main section.
     */

    public function pluginMainSectionText()
    {
        echo '<p>' . __('Google Webfonts for WooThemes Woo Framework. All fonts listed here are available to the theme.') . '</p>';
        echo '<p>' . __('Fonts shown selected here have been used in the theme.') . '</p>';
        echo '<p>';
        echo __('To preview any fonts, select the fonts from the "All Google Webfonts" list and press the preview button.');
        echo " ";
        echo __('A font shown with a weight "+italic" is a weight that has separate variants for the italicized and non-italicized styles.');
        echo " ";
        echo __('Previews will display Google variants where available; the browser will make its own decision on how to display styles and weights where variants are not available.');
        echo " ";
        echo __('The previews will also make an attempt to display character glyphs that are only available to the subsets selected.');
        echo '</p>';
    }

    //
    // Display the input fields.
    //

    /**
     * Render the Google API Key input field.
     */

    public function googleApiKeyField() {
        $option = get_option(self::settings_option_api_key, '');
        echo "<input id='" . self::settings_option_api_key . "' name='" . self::settings_option_api_key . "' size='80' type='text' value='{$option}' />";
    }

    /**
     * Render the font subset input fields (checkboxes).
     * Give the box a class 'gwfc_google_webfont_subset_select' so the font previewer can find the checked boxes.
     */

    public function fontSubsetField() {
        $option = get_option(self::settings_option_font_subset, 'latin');
        $options = explode(',', $option);

        foreach($this->all_font_subsets as $value => $label) {
            $bold_style = (substr($label, 0, 1) == '*' ? ' font-weight: bold;' : '');

            echo "<label for='" . self::settings_option_font_subset . "_$value' style='white-space: nowrap; {$bold_style}'>";
            $checked = (in_array($value, $options) ? "checked='checked'" : '');
            echo "<input class='gwfc_google_webfont_subset_select' type='checkbox' id='" . self::settings_option_font_subset . "_$value' name='" . self::settings_option_font_subset . "[$value]' value='$value' $checked />";
            echo "$label</label>&nbsp; ";
        }

        echo "<p>" . _('Previews are available only for subsets marked "*".') . "</p>";
    }

    /**
     * Render the font weight input fields (checkboxes).
     * Give the box a class 'gwfc_google_webfont_subset_select' so the font previewer can find the checked boxes.
     */

    public function fontWeightField() {
        $option = get_option(self::settings_option_font_weight, implode(',', $this->default_weights));
        $options = explode(',', $option);

        foreach($this->all_font_weights as $value => $label) {
            // Highlight the three default weights.
            if (in_array($value, $this->default_weights)) echo "<strong><em>";

            echo "<label for='" . self::settings_option_font_weight . "_$value' style='white-space: nowrap;'>";

            $checked = (in_array($value, $options) ? "checked='checked'" : '');

            // Group the weights into rows of three.
            if ($value != '100' && ($value-100) % 300 == 0) echo "<br />";

            echo "<input class='gwfc_google_webfont_weight_select' type='checkbox' id='" . self::settings_option_font_weight . "_$value' name='" . self::settings_option_font_weight . "[$value]' value='$value' $checked />";

            echo "$value: $label</label>&nbsp; ";

            if (in_array($value, $this->default_weights)) echo "</em></strong>";
        }

        echo "<p>" . _('The Woo Framework font chooser GUI supports weights 300, 400 and 700 only at present.') . "</p>";
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
     * Expand an array of variants from Google into a more friendly display-only list.
     */

    public function expandGoogleVariants($variants)
    {
        $variants = str_replace(
            array('i', '100', '200', '300', '400', '500', '600', '700', '800', '900'),
            array(' italic', 'ultra-light', 'light', 'thin', 'regular', 'medium', 'semi-bold', 'bold', 'extra-bold', 'ultra-bold'),
            $variants
        );

        // Combine variants where one is an italic version of another.
        // The lists of variants can get very long if we don't do this.
        // We look for {variant} and {variant italic} and combine them to a single {variant+italic}
        $normal = array();
        $italics = array();
        foreach($variants as $variant) {
            if (strpos($variant, ' italic') !== false) {
                $italics[$variant] = $variant;
            } else {
                $normal[$variant] = $variant;
            }
        }

        // Rebuild the variants list.
        $variants = array();
        foreach($normal as $variant) {
            if (isset($italics[$variant . ' italic'])) {
                $variants[] = $variant . '+' . 'italic';
                unset($italics[$variant . ' italic']);
            } else {
                $variants[] = $variant;
            }
        }

        // Merge in any italic variants left over, that don't have non-italic versions.
        if ( ! empty($italics)) {
            $variants = array_merge($variants, $italics);
        }

        // Remove any reference to "regular+" to help keep the lengths of the options down.
        // e.g. "regular" would stay, but "regular+bold" would just become "bold".
        return str_replace('regular+', '', $variants);
    }

    /**
     * Render the list of original framework fonts.
     */

    public function oldFontsField() {
        $used_fonts = $this->fontsUsedInTheme();

        if (empty($this->old_woo_google_fonts)) {
            _e(
                'No framework fonts found. '
                . 'You must use a theme from WooThemes for this plugin in work.'
            );
        } else {
            echo '<select name="' . self::settings_field_old_fonts . '" multiple="multiple" size="10" class="' . self::settings_field_select_class . ' old-fonts">';

            $i = 1;
            foreach($this->old_woo_google_fonts as $font) {
                $selected = (isset($used_fonts[$font['name']])) ? ' selected="selected"' : '';

                echo '<option value="'. $font['name'] .'"' . $selected . '>' 
                    . $font['name'] 
                    . (!empty($font['variant']) ? ' (' . $this->expand_woo_variants($font['variant']) . ')' : '')
                    . '</option>';
            }

            echo '</select> (' . count($this->old_woo_google_fonts) . ')';
        }
    }

    /**
     * Render the list of new fonts this plugin makes available.
     */

    public function newFontsField() {
        $used_fonts = $this->fontsUsedInTheme();

        echo '<select name="' . self::settings_field_new_fonts . '" multiple="multiple" size="10" class="' . self::settings_field_select_class . ' new-fonts">';

        $i = 1;
        foreach($this->current_google_fonts as $name => $variants) {
            $selected = (isset($used_fonts[$name])) ? ' selected="selected"' : '';

            echo '<option value="'. htmlspecialchars($name) .'"' . $selected
                . ' variants=":' . htmlspecialchars(implode(',', $variants)) . '"'
                . '>' 
                . htmlspecialchars($name)
                . (!empty($variants) ? ' (' . implode(', ', $this->expandGoogleVariants($variants)) . ')' : '')
                . '</option>';
        }

        echo '</select> (' . count($this->current_google_fonts) . ')';

        // Optionally list the fonts as an encoded JSON file.
        // Trigger this code by putting "&export=1" on the end of the plugin settins page URL.
        if (!empty($_GET['export'])) {
            echo '<p>' . __('Encoded list of fonts to update fonts.json') . '</p>';
            echo '<textarea rows="20" cols="40">';
            echo $this->prettyPrintJson(json_encode($this->current_google_fonts));
            echo '</textarea>';
        }
    }

    /**
     * Indents a flat JSON string to make it more human-readable. See:
     * http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     */
    function prettyPrintJson($json) {

        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }


    /**
     * Preview any selected fonts.
     */

    public function previewFontsField() {
        //
        // Here provide the preview.
        //

        echo '<p>';
        echo '<input type="submit" id="preview-fonts" value="' . __('Preview Fonts') . '" onClick="jQuery().gwfwFontPreview({clear: true}); return false;" />';
        echo ' <input type="checkbox" value="1" id="preview-italic" name="preview-italic" />';
        echo ' <label for="preview-italic">' . __('Preview italic style') . '</label>';
        echo ' <select name="preview-weight" id="preview-weight">';
        foreach($this->all_font_weights as $code => $name) {
            $selected = ($code == '400' ? ' selected="selected"' : '');
            $style = ($code == '400' || $code == '700' || $code == '300' ? 'style="font-weight: bold; color: #006600;"' : '');
            echo '<option ' . $style . ' value="' . $code . '"' . $selected . '>' . $code . ': ' . $name . '</option>';
        }
        echo ' </select>';
        echo '</p>';

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

    public function googleApiKeyValidate($input) {
        // Make sure it is a URL-safe string.
        if ($input != rawurlencode($input)) {
            add_settings_error(self::settings_field_api_key, 'texterror', __('API key contains invalid characters'), 'error');
        } else {
            // If emtpy, then use the default API key.

            if (empty($input)) {
                $input = $this->google_api_key;
            }

            // If valid (we hope), then replace the current cache with new Google fonts.
            // This seems wrong here, because we have not stored the API key at this point.
            // We'll pass the API key in and do the fetch anyway, but I suspect the fetch is
            // being done again anyway.

            $fonts = $this->getGoogleFonts($input);
            if ( ! empty($fonts)) {
                set_transient($this->trans_cache_name, $fonts);
            }
        }

        return $input;
    }

    /**
     * Validate the submitted fields.
     */

    public function fontSubsetValidate($input) {
        // The very first time we come here, $input will be a string and not an array.
        // I have no idea why, but let's catch it to suppress error messages.
        if (is_string($input)) $input = array($input);

        if (is_array($input)) {
            // Make sure submitted values are in the allowable list, then convert them into a string.
            $all_font_subsets = $this->all_font_subsets;

            foreach($input as $key => $value) {
                if (!isset($all_font_subsets[$key])) unset($input[$key]);
            }
            $input = implode(',', array_keys($input));
            if (empty($input)) $input = 'latin';
        } else {
            add_settings_error(self::settings_field_font_subset, 'texterror', __('Invalid subset selection made' . print_r($input, true)), 'error');
        }

        return $input;
    }

    /**
     * Validate the submitted fields.
     */

    public function fontWeightValidate($input) {
        // The very first time we come here, $input will be a string and not an array.
        // I have no idea why, but let's catch it to suppress error messages.
        if (is_string($input)) $input = array($input);

        if (is_array($input)) {
            // Make sure submitted values are in the allowable list, then convert them into a string.
            $all_font_weights = $this->all_font_weights;

            foreach($input as $key => $value) {
                if (!isset($all_font_weights[$key])) unset($input[$key]);
            }
            $input = implode(',', array_keys($input));
            if (empty($input)) $input = '300,400,700';
        } else {
            add_settings_error(self::settings_field_font_weight, 'texterror', __('Invalid weight selection made'), 'error');
        }

        return $input;
    }

    /**
     * Get a list of all the fonts used in the theme at present.
     * TODO: check out global $woo_used_google_fonts first.
     */

    public function fontsUsedInTheme()
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
     * This is the same format as returned by getFallbackFonts(), and is
     * actually used to generate the content of fonts.json
     */

    public function getGoogleFontData($google_api_key = null)
    {
        if (empty($google_api_key)) $google_api_key = get_option('gwfc_google_api_key', '');
        $font_list = false;

        // If not API key is set yet, then abort.
        if (empty($google_api_key)) return $font_list;

        // The API key should be URL-safe.
        // We need to ensure it is when setting it in the admin page.
        $api_data = wp_remote_get($this->api_url . $google_api_key);

        // If the fetch failed, then report it to the admin.
        if (is_wp_error($api_data)) {
            $error_message = $api_data->get_error_message();
            $this->admin_notice = "Error fetching Google font: $error_message";
            return $font_list;
        }

        $response = $api_data['response'];

        if (200 === $response['code']) {
            $font_list = json_decode($api_data['body'], true);
        }

        // At this point we could try deciphering the error messages that Google returns,
        // but they are complex structures and the cost/benefit is probably not worth it.

        if (empty($font_list) || !is_array($font_list)) {
            $errors = json_decode($api_data['body'], true);
            if (isset($errors['error']['errors'][0]) && is_array($errors['error']['errors'][0])) {
                $error_details = array();
                foreach($errors['error']['errors'][0] as $k => $v) {
                    $error_details[] = "$k: $v";
                }
                $this->admin_notice = implode('; ', $error_details);
            }
            return $font_list;
        }

        $fonts = array();
        foreach($font_list['items'] as $font) {
            $variants = $font['variants'];

            // Normalise the weights. Make sure every variant has
            // an explicit weight. This makes it easier to process later.
            // Also abreviate "italic" to "i".
            //
            // Font weights are (as defined by Google's font overview pages):
            //  100 ultra-light
            //  200 light
            //  300 book (Woo Framework calls this "thin", Google calls this "light")
            //  400 normal
            //  500 medium
            //  600 semi-bold
            //  700 bold
            //  800 extra-bold
            //  900 ultra-bold
            // Every list of font weights have different mappings, so beware this is
            // not an exact science.

            // Simplify the data a little for storage, and make it more consistent.

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

        // Sort by keys, which will be the font name.
        ksort($fonts);

        return $fonts;
    }

    /**
     * Get the full list of fonts from Google.
     * TODO: allow the variants to be restricted to a smaller set than is available.
     * For example, Ultrabold (900) is not available in Canvas, so there is no point loading it.
     */

    public function getGoogleFonts($google_api_key = null)
    {
        // Get the font data from Google.
        $font_list = $this->getGoogleFontData($google_api_key = null);

        if (empty($font_list)) return $font_list;

        // Format the font data in the way the Woo Framework expects.

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

