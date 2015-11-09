<?php
/**
 * @package Google Webfonts For Wootheme Framework
 */
/*
Plugin Name: Google Webfonts For Woo Framework
Plugin URI: https://github.com/academe/google-webfonts-for-woo-framework
Description: Adds all missing Google webfonts to the WooThemes themes that use the Woo Framework.
Version: 1.6.3
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
 * We are now moving the key to a new option, so it is less likely to
 * clash with other plugins. We are then providing a default key that
 * everyone can use. The key has 100k uses each day, which should cover
 * many millions of sites, unless they all go into the admin pages on
 * on the same day.
 */

function GoogleWebfontsForWooFramework_activation()
{
    global $GWFC_OBJ;

    // Get the Google API key.
    $google_api_key = get_option('gwfc_google_api_key');

    // If no api key option exists - either empty string or not set - then create one now.
    if ($google_api_key === false || $google_api_key === '') {
        // If one already exists under the old option name, then take that as the new value.
        $google_api_key = get_option('google_api_key');

        // If still no value, then give it the default browser key.
        if ( ! $google_api_key) {
            $google_api_key = 'AIzaSyA2HFVb-wF8PupiqpgNtAid-0hFcZxo28Y';
        }

        // Add or update the option.
        if (add_option('gwfc_google_api_key', $google_api_key, false, 'no') === false) {
            update_option('gwfc_google_api_key', $google_api_key);
        }
    }

    // Safety measure - some users finding the object is not there.
    // Can't reproduce it myself.
    if (empty($GWFC_OBJ) || is_object($GWFC_OBJ)) return;

    // If fonts have previously been cached (in an earlier version) then the cache
    // may have an expiration. If so, refresh the cache now without an expiration.
    $fonts = get_transient($GWFC_OBJ->trans_cache_name);
    if (empty($fonts)) $fonts = $GWFC_OBJ->getFallbackFonts();
    if (!empty($fonts)) set_transient($GWFC_OBJ->trans_cache_name, $fonts);
}
register_activation_hook(__FILE__, 'GoogleWebfontsForWooFramework_activation');

// Also check for a plugin upgrade, and run the same activation code.
// This feels really messy, and throws too much crap into the global space,
// but there are no official recommended ways of handling upgrades on a plugin
// update.
function GoogleWebfontsForWooFramework_loaded()
{
    $version = '1.6.0';
    if (get_option( 'gwfc_plugin_version' ) != $version) {
        GoogleWebfontsForWooFramework_activation();
        update_option( 'gwfc_plugin_version', $version );
    }
}
add_action( 'plugins_loaded', 'GoogleWebfontsForWooFramework_loaded' );


// Set the main plugin as a global object.
// There is a standard visitor version and an extended admin version.
require(dirname(__FILE__) . '/google-webfonts-for-woo-framework-base.php');
if (is_admin()) {
    require(dirname(__FILE__) . '/google-webfonts-for-woo-framework-admin.php');
    $GWFC_OBJ = new GoogleWebfontsForWooFrameworkAdmin();
} else {
    $GWFC_OBJ = new GoogleWebfontsForWooFramework();
}

$GWFC_OBJ->init();

// Here we override the function that the Woo Framework uses to put the fonts into
// the page head. We do this as early as we can, to get it in before other Woo plugins
// and themes. However, we only need to do this if we are using subsets any other then
// 'latin'.
// Since we are now always restricting the weights now, this alternative font loader is always run.

if ( ! function_exists( 'woo_google_webfonts' )) {
    function woo_google_webfonts() {
        global $google_fonts;
        global $GWFC_OBJ;
        $fonts = '';
        $output = '';

        // Setup Woo Options array
        global $woo_options;

        // Go through the options
        if ( !empty($woo_options) ) {

            // Get the abbreviated font weights that have been selected by the admin.
            $selected_font_weights = explode(',', $GWFC_OBJ->abbreviateVariants($GWFC_OBJ->font_weights));

            // Add on italic variants for each of the selected font weights.
            $selected_font_weights_i = array();
            foreach($selected_font_weights as $selected_font_weight) {
                $selected_font_weights_i[] = $selected_font_weight . 'i';
            }
            $selected_font_weights = array_merge($selected_font_weights, $selected_font_weights_i);

            $fonts_used = array();
            foreach ( $woo_options as $option ) {
                if ( is_array($option) && isset($option['face']) ) $fonts_used[$option['face']] = $option['face'];
            }

            $family = array();

            foreach ($google_fonts as $font) {
                if (isset($fonts_used[$font['name']])) {
                    // Filter each list of variants to just the subset that has been selected.
                    $variants = explode(',', ltrim($font['variant'], ':'));
                    $variants = array_intersect($variants, $selected_font_weights);

                    // If there are no variants for this font, then skip it completely.
                    if (empty($variants)) continue;

                    $family[] = $font['name'] . ':' . implode(',', $variants);
                }
            }

            // Output google font css in header.
            if ( !empty($family) ) {
                $output .= "\n<!-- Google Webfonts (for subsets: $GWFC_OBJ->font_subsets and weights: " .implode(',', $selected_font_weights). ") -->\n";

                $url = 'http' . ( is_ssl() ? 's' : '' )
                    .'://fonts.googleapis.com/css?family='
                    . urlencode(implode('|', $family))
                    . '&subset=' . urlencode($GWFC_OBJ->font_subsets);

                $output .= '<link href="' . esc_html($url) . '"'
                    . ' rel="stylesheet" type="text/css" />'
                    . "\n";

                echo $output;
            }
        }
    } // End woo_google_webfonts()
}
