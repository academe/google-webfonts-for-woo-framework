<?php
/**
 * @package Google Webfonts For Wootheme Framework
 */
/*
Plugin Name: Google Webfonts For Woo Framework
Plugin URI: https://github.com/academe/google-webfonts-for-woo-framework
Description: Adds all missing Google webfonts to the WooThemes themes that use the Woo Framework.
Version: 0.9.8
Author: Jason Judge
Author URI: http://www.academe.co.uk/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
 * Set default settings on installation.
 */

register_activation_hook(__FILE__, function() {
    $google_api_key = get_option('google_api_key');
    if ($google_api_key === false) {
        add_option('google_api_key', '', false, true);
    }
});

// Set the main plugin as a global object.
$GWFC_OBJ = new GoogleWebfontsForWooFramework();
$GWFC_OBJ->init();

class GoogleWebfontsForWooFramework
{
    // The name of the cahce for the the fonts.
    public $trans_cache_name = 'google_webfonts_for_woo_framework_cache';

    // The time the fonts are cached for, before we fetch a new batch from Google.
    // TODO: make the time configurable, which would include "indefinite".
    public $cache_time = 43200; // 60*60*12 seconds = 12 hours

    // New fonts that this plugin brings to the framework.
    public $new_fonts = array();

    // A snapshot of fonts the framework includes.
    public $old_fonts = array();

    // Initilialise the plugin.
    public function init() {
        //$fonts = $this->getGoogleFontsCached(); var_dump($fonts);

        // Add the missing fonts in the admin page.
        add_action('admin_head', array($this, 'action_add_fonts'), 20);

        // Add the missing fonts to the non-admin pages too.
        // It needs to be an early action (5) to get in before the theme hook
        // that uses the font list.
        add_action('wp_head', array($this, 'action_add_fonts'), 5);

        if (is_admin()) {
            // Add the admin menu.
            add_action('admin_menu', array($this, 'admin_menu'));

            // Register the settings.
            add_action('admin_init', array($this, 'register_settings'));
        }
    }

    public function admin_menu()
    {
        // And "options_page" will go under the settings menu as a sub-menu.
        add_options_page(
            'Google Webfonts for Woo Framework Options',
            'Google Webfonts for Woo Framework',
            'manage_options',
            'gw-for-wooframework',
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
        echo '<h2>Google Webfonts for Woo Framework Options</h2>';

        echo '<form method="post" action="options.php">';

        settings_fields('gwfc-group');
        do_settings_sections('gwfc_main_section');

        echo '<table class="form-table">';

        echo '</table>';

        submit_button();
        echo '</form>';

        echo '</div>';
    }

    public function register_settings()
    {
        // Register the settings.

        register_setting(
            'gwfc-group', 
            'google_api_key',
            array($this, 'google_api_key_validate')
        );

        // Register a section in the page.

        add_settings_section(
            'gwfc_main', 
            'Main Settings', 
            array($this, 'plugin_main_section_text'),
            'gwfc_main_section'
        );

        // Add fields to the section.

        // The API key.
        add_settings_field(
            'google_api_key',
            'Google Developer API Key',
            array($this, 'google_api_key_field'),
            'gwfc_main_section',
            'gwfc_main'
        );

        // List of added fonts (read-only).
        add_settings_field(
            'old_fonts',
            'Framework fonts (view only)',
            array($this, 'old_fonts_field'),
            'gwfc_main_section',
            'gwfc_main'
        );

        // List of added fonts (read-only).
        add_settings_field(
            'new_fonts',
            'New fonts introduced (view only)',
            array($this, 'new_fonts_field'),
            'gwfc_main_section',
            'gwfc_main'
        );
    }

    // Summary text/introduction to the main section.

    public function plugin_main_section_text()
    {
        echo '<p>Google Webfonts for WooThemes Woo Framework.</p>';
    }

    // Display the input fields.

    // The Google API Key input field.
    public function google_api_key_field() {
        $option = get_option('google_api_key', '');
        echo "<input id='google_api_key' name='google_api_key' size='80' type='text' value='{$option}' />";
    }

    // Display the list of original framework fonts.
    public function old_fonts_field() {
        if (empty($this->old_fonts)) {
            echo 'No framework fonts found'; // TODO: translate.
        } else {
            echo '<select name="old_fonts" multiple="multiple" size="10">';

            $i = 1;
            foreach($this->old_fonts as $font) {
                echo '<option value="'. $i++ .'">' .$font['name']. '</option>';
            }

            echo '</select>';
        }
    }

    // Display the list of new fonts this plugin makes available.
    public function new_fonts_field() {
        if (empty($this->new_fonts)) {
            echo 'No new fonts found'; // TODO: translate.
        } else {
            echo '<select name="new_fonts" multiple="multiple" size="10">';

            $i = 1;
            foreach($this->new_fonts as $font) {
                echo '<option value="'. $i++ .'">' .$font['name']. '</option>';
            }

            echo '</select>';
        }
    }

    // Validate the submitted fields.

    public function google_api_key_validate($input) {
        // Make sure it is a URL-safe string.
        if ($input != rawurlencode($input)) {
            add_settings_error('google_api_key', 'texterror', 'API key contains invalid characters', 'error');
        } else {
            // If valid, then discard the current fonts cache, so a fresh fetch is
            // done with the new key.
            delete_transient($this->trans_cache_name);
        }

        return $input;
    }


    /**
     * Insert any missing Google fonts.
     */

    public function action_add_fonts()
    {
        // This array is what the Woo Framework defines and uses.
        global $google_fonts;

        // If there is no global google fonts list, bail out.
        if (empty($google_fonts) || !is_array($google_fonts)) return;

        // Take a snapshot, before we mess with the list, and sort them by name.
        $this->old_fonts = $google_fonts;
        uasort($this->old_fonts, function($a, $b) {
            return ($a['name'] < $b['name']) ? -1 : 1;
        });

        // Get the full list of Google fonts available.
        $all_fonts = $this->getGoogleFontsCached();
        if (!$all_fonts) return;

        // Make a list of font families we have in the list already.
        $families = array();
        foreach($google_fonts as $font) {
            $families[$font['name']] = true;
        }

        // Now we have a list to check against, we can insert the fonts that
        // are missing. The Woo Framework will deal with sorting this list later, so we
        // just tag them on the end.
        foreach($all_fonts as $font) {
            if (isset($families[$font['name']])) continue;

            $this->new_fonts[] = $font;
        }

        // If we have any fonts to add, then add them to the list.
        if (!empty($this->new_fonts)) $google_fonts += $this->new_fonts;
    }

    /**
     * Get the full list of fonts from Google, formated for Woo Framework and
     * cached.
     */

    public function getGoogleFontsCached()
    {
        // Transient caching.
        if (false === ($fonts = get_transient($this->trans_cache_name))) {
            $fonts = $this->getGoogleFonts();
            if (!empty($fonts)) set_transient($this->trans_cache_name, $fonts, $this->cache_time);
        }

        return $fonts;
    }

    /**
     * Get the full list of fonts from Google.
     */

    public function getGoogleFonts()
    {
        $google_api_key = get_option('google_api_key', '');
        $api_url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=';
        $font_list = false;

        // If not API key is set yet, then abort.
        if (empty($google_api_key)) return $font_list;

        // The API key should be URL-safe.
        // We need to ensure it is when setting it in the admin page.
        $api_data = wp_remote_get($api_url . $google_api_key);

        $response = $api_data['response'];

        if (200 === $response['code']) {
            $font_list = json_decode($api_data['body'], true);
        }

        // Now we should have $font_list.
        // If it is valid, then save it in a more permanent cache after some processing.
        // Woo Framework needs entries like this:
        //    array( 'name' => "Caudex", 'variant' => ':r,b,i,bi')
        // while Google provides a very different syntax, using a mix of font weights (instead
        // of bold/regular/etc) and names (italic/regular/etc).
        // It is not yet clear just how Woo Framework uses these variants beyond just passing them to Google.
        // e.g. These provided by Google API: "regular", "italic", "900", "900italic"
        // need to be translated into this: ":400,900italic,900,400italic" so it can
        // be used on the web page. I think Woo Framework does actually *use* these variants,
        // which it could in the admin interface, but it just blindly passes them to
        // Google to ask for all variants to be passed back to the web browser, which TBH
        // is inneficient, as it is requresting variants that may never be used.
        // https://developers.google.com/webfonts/docs/getting_started
        // According to the Google docs, we can specify the full names, abbreviations
        // or the weights - all are equivalent.

        // If we don't have an array of fonts, bail out now.
        if (empty($font_list) || !is_array($font_list)) return $font_list;

        // We want to go through the list and abbreviate it.
        // Some of the variant names are abreviated. We could go further, with the weights.
        // For example, 700 == bold == b == 7
        $fonts = array();
        foreach($font_list['items'] as $font) {
            if (!empty($font['variants'])) {
                $variants = preg_replace(
                    array('/^regular$/', '/italic/', '/bold/', '/^700$/', '/^700i$/'),
                    array('r', 'i', 'b', 'b', 'bi'),
                    $font['variants']
                );
                // Woo Framework expects the leading ":" to be included in the variant list. It does
                // not insert it at the point of use.
                $variant = ':' . implode(',', $variants);
            } else {
                $variant = '';
            }

            $fonts[] = array(
                'name' => $font['family'],
                'variant' => $variant,
            );
        }

        return $fonts;
    }
}

