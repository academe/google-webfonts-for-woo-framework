<?php
/**
 * @package Google Webfonts For Wootheme Framework
 */
/*
Plugin Name: Google Webfonts For Woo Framework
Plugin URI: https://github.com/academe/google-webfonts-for-woo-framework
Description: Adds all missing Google webfonts to the WooThemes themes that use the Woo Framework.
Version: 1.0.0
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
 * CHECKME: can this be wrapped with is_admin()?
 */

function GoogleWebfontsForWooFramework_activation()
{
    $google_api_key = get_option('google_api_key');
    if ($google_api_key === false) {
        add_option('google_api_key', '', false, true);
    }
}
register_activation_hook(__FILE__, 'GoogleWebfontsForWooFramework_activation');

/*
// PHP5.3 only.
register_activation_hook(__FILE__, function() {
    $google_api_key = get_option('google_api_key');
    if ($google_api_key === false) {
        add_option('google_api_key', '', false, true);
    }
});
*/

// Set the main plugin as a global object.
// There is a standard visitor version and an admin extended version.
if (is_admin()) {
    require(dirname(__FILE__) . '/google-webfonts-for-woo-framework-admin.php');
    $GWFC_OBJ = new GoogleWebfontsForWooFrameworkAdmin();
} else {
    $GWFC_OBJ = new GoogleWebfontsForWooFramework();
}

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

    // The Google API URL we will fetch the font list from.
    public $api_url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=';

    // Admin notice text.
    public $admin_notice = '';

    // Initilialise the plugin.
    public function init() {
        // Add the missing fonts to the non-admin pages too.
        // It needs to be an early action (5) to get in before the theme hook
        // that uses the font list.
        add_action('wp_head', array($this, 'action_add_fonts'), 5);
    }

    /**
     * Insert any missing Google fonts.
     * We would use a closure in PHP53, but this is the WordPress world.
     */

    public function sort_font_array($a, $b)
    {
        return ($a['name'] < $b['name']) ? -1 : 1;
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
        uasort($this->old_fonts, array($this, 'sort_font_array'));

        /*
        // PHP5.3 only.
        uasort($this->old_fonts, function($a, $b) {
            return ($a['name'] < $b['name']) ? -1 : 1;
        });
        */

        // Get the full list of Google fonts available.
        $all_fonts = $this->getGoogleFontsCached();
        if (!$all_fonts) return;

        // Make a list of font families we have in the list already.
        $families = array();
        foreach($google_fonts as $font) {
            $families[$font['name']] = $font['variant'];
        }

        // Now we have a list to check against, we can insert the fonts that
        // are missing. The Woo Framework will deal with sorting this list later, so we
        // just tag them on the end.
        $variant_updates = array();
        foreach($all_fonts as $font) {
            if (isset($families[$font['name']])) {
                // The font exists, but does it need its variants updated?
                if ($families[$font['name']] == $font['variant']) continue;

                // Yes, the variants are different.
                // We need to update the existing font. They are not indexed well, unfortunatly,
                // so we loop through them all. We'll just keep a list an do that at the end.
                $variant_updates[$font['name']] = $font['variant'];
                continue;
            }

            $this->new_fonts[] = $font;
        }

        if (!empty($variant_updates)) {
            foreach($google_fonts as $key => $font) {
                if (isset($variant_updates[$font['name']])) {
                    $google_fonts[$key]['variant'] = $variant_updates[$font['name']];
                    $this->old_fonts[$key]['variant'] = $variant_updates[$font['name']];
                }
            }
        }

        // If we have any fonts to add, then add them to the list.
        if (!empty($this->new_fonts)) $google_fonts = array_merge($google_fonts, $this->new_fonts);
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
     * Display an admin notice if there is one.
     */

    public function display_admin_notice() {
        if ($this->admin_notice == '') return;
        echo '<div class="updated">';
        echo $this->admin_notice;
        echo '</div>';
    }

    /**
     * Get the full list of fonts from Google.
     */

    public function getGoogleFonts()
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

