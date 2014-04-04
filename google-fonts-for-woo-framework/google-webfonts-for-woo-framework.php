<?php
/**
 * @package Google Webfonts For Wootheme Framework
 */
/*
Plugin Name: Google Webfonts For Woo Framework
Plugin URI: https://github.com/academe/google-webfonts-for-woo-framework
Description: Adds all missing Google webfonts to the WooThemes themes that use the Woo Framework.
Version: 1.4.2
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
    global $GWFC_OBJ;

    // If no api key option exists, then create a blank one now.
    $google_api_key = get_option('google_api_key');
    if ($google_api_key === false) {
        add_option('google_api_key', '', false, true);
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

// Set the main plugin as a global object.
// There is a standard visitor version and an extended admin version.
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

if ( ! function_exists( 'woo_google_webfonts' ) && (true || $GWFC_OBJ->font_subsets != 'latin' || $GWFC_OBJ->font_weights != implode(',', $GWFC_OBJ->default_weights))) {
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

            foreach ($google_fonts as $font) {
                if (isset($fonts_used[$font['name']])) {
                    // Filter each list of variants to just the subset that has been selected.
                    $variants = explode(',', ltrim($font['variant'], ':'));
                    $variants = array_intersect($variants, $selected_font_weights);

                    // If there are no variants for this font, then skip it completely.
                    if (empty($variants)) continue;

                    $fonts .= $font['name'] . ':' . implode(',', $variants) . "|";
                }
            }

            // Output google font css in header
            if ( $fonts ) {
                $fonts = str_replace( " ", "+", $fonts );
                $output .= "\n<!-- Google Webfonts (for subsets: $GWFC_OBJ->font_subsets and weights: " .implode(',', $selected_font_weights). ") -->\n";
                $output .= '<link href="http'
                    . ( is_ssl() ? 's' : '' )
                    .'://fonts.googleapis.com/css?family='
                    . trim($fonts, '|')
                    . '&amp;subset=' . $GWFC_OBJ->font_subsets
                    . '" rel="stylesheet" type="text/css" />'
                    . "\n";

                echo $output;
            }
        }
    } // End woo_google_webfonts()
}


class GoogleWebfontsForWooFramework
{
    // The name of the cahce for the the fonts.
    public $trans_cache_name = 'google_webfonts_for_woo_framework_cache';

    // A snapshot of fonts the framework includes.
    // Format is the Woo array('name'=>{font},'variant'=>':'{variant-list})
    public $old_woo_google_fonts = array();

    // The Google API URL we will fetch the font list from.
    public $api_url = 'https://www.googleapis.com/webfonts/v1/webfonts?key=';

    // Admin notice text.
    public $admin_notice = '';

    // The current subsets selected for the site, comma-separated.
    public $font_subsets = 'latin';

    // The name of the option to store the subset(s) selected for the site.
    public $subset_option_name = 'gwfc_google_webfont_subset';

    // The name of the option to store the weights selected for the site.
    public $weight_option_name = 'gwfc_google_webfont_weight';

    // The default weights, until alternatives are selected.
    public $default_weights = array('300', '400', '700');

    // Initilialise the plugin.
    public function init() {
        // Add the missing fonts to the non-admin pages too.
        // It needs to be an early action (5) to get in before the theme hook
        // that uses the font list.
        add_action('wp_head', array($this, 'action_set_fonts'), 5);

        // Get the font subsets that have been chosen.
        $subset = get_option($this->subset_option_name, 'latin');
        if (empty($subset)) $subset = 'latin';
        $this->font_subsets = $subset;

        // Get the font weights that have been chosen.
        $weight = get_option($this->weight_option_name, implode(',', $this->default_weights));
        if (empty($weight)) $weight = implode(',', $this->default_weights);
        $this->font_weights = $weight;
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

    public function action_set_fonts()
    {
        // This array is what the Woo Framework defines and uses.
        global $google_fonts;

        // If there is no global google fonts list, bail out.
        // It probably means the Woo framework has fundamentally changed the way it works.
        if (empty($google_fonts) || ! is_array($google_fonts)) return;

        // Take a snapshot, before we mess with the list, and sort them by name.
        // A closure would be nice here, but we are restricted to PHP 5.2 for WP.
        // Only do this if we are in an admin page; we don't need the "before" list any
        // other time.
        if (is_admin()) {
            $this->old_woo_google_fonts = $google_fonts;
            uasort($this->old_woo_google_fonts, array($this, 'sort_font_array'));
        }

        // Get the full list of Google fonts available.
        $all_fonts = $this->getGoogleFontsCached();

        if ( ! $all_fonts) return;

        // Completely replace the Woo Framework list with the new list.
        $google_fonts = $all_fonts;
    }

    /**
     * Get the full list of Google fonts, formatted for Woo Framework.
     * We look first in the cache generated by the settings page, and then in
     * the local cache file if not in the transient cache generated in the settings
     * page.
     */

    public function getGoogleFontsCached()
    {
        // Transient caching.
        if (false === ($fonts = get_transient($this->trans_cache_name))) {
            $fonts = $this->getFallbackFonts();

            // Cache indefinitely. The cache will be refreshed on a visit to the settings page.
            if (!empty($fonts)) set_transient($this->trans_cache_name, $fonts);
        }

        return $fonts;
    }

    /**
     * Returns fallback font data, in the same format as getGoogleFontData().
     */

    public function getFallbackFontData()
    {
        $file = dirname(__FILE__) . '/fonts.json';

        if (is_readable($file)) {
            $data = file_get_contents($file);
            return json_decode($data, true);
        }

        return null;
    }

    /**
     * Returns fonts, formatted for Woo Framework, pulled from the local fallback file.
     */

    public function getFallbackFonts()
    {
        $font_data = $this->getFallbackFontData();
        $woo_fonts = array();

        if ( ! empty($font_data)) {
            foreach($font_data as $family => $variants) {
                $woo_fonts[] = array(
                    'name' => $family,
                    'variant' => ':' . implode(',', $this->abbreviateVariants($variants))
                );
            }
        }

        return $woo_fonts;
    }

    /**
     * Abbreviate an array of variants so it is as short as possible.
     * This helps to convert our internal font data to the Woo Framework format.
     */

    public function abbreviateVariants($variants)
    {
        return str_replace(
            array('400', '700'),
            array('r', 'b'),
            $variants
        );
    }
}


