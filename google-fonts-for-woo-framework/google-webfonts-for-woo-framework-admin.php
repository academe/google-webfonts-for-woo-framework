<?php

/**
 * This script contains an admin-section extended version of the plugin.
 */

class GoogleWebfontsForWooFrameworkAdmin extends GoogleWebfontsForWooFramework
{
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

            echo '</select> (' . count($this->old_fonts) . ')';
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

            echo '</select> (' . count($this->new_fonts) . ')';
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

}

