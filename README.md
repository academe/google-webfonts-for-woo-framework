# Introduction

This is a plugin for WordPress. Its purpose is to pull all Google webfonts into the WooThemes Canvas
plugin - or any other plugin that uses the WooThemes framework - through the Google Developer API.
Also additional subsets (sets of international glyphs) can be selected for all loaded fonts.

# Installation

1. Clone this repository.
2. Put the google-fonts-for-woo-framework directory and all its contents into the archive google-fonts-for-woo-framework.zip
3. Install the zip file into WordPres as a plugin in the normal way.

Alternatively download the plugin from within your WordPress installation. The plugin page is:

http://wordpress.org/extend/plugins/google-fonts-for-woo-framework/

The "releases" directory holds released zip files for convenience. The latest release is also available as google-fonts-for-woo-framework-latest.zip

# Notes

You will need a Google Developer API Key to get access to the list of fonts that Google supplies. The plugin
has a settings page that allows you to enter the API key. The plugin will refresh its list of fonts only
when entering the settings page. If no valid API key is set in the plugin, then it will fall back to a
fonts.json file that is refreshed each time this plugin is released.

The Google Developer API Key can be obtained for free here:

http://code.google.com/apis/console

Some simple steps for obtaining the key are shown here:

http://code.garyjones.co.uk/google-developer-api-key

The code is styled more to PSR-1/PSR-2 than to the standard WordPress format. It's just a personal
preference, and that's the way it is.

