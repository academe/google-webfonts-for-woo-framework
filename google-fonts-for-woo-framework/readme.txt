=== Google Webfonts For Woo Framework ===
Contributors: judgej
Donate link: 
Tags: woothemes, google webfonts
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 0.9.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Give the WooThemes framework access to the full range of current Google Webfonts.

== Description ==

The purpose of this plugin is to make all available Google webfonts available to the WooThemes Canvas theme, and any other themes that use the WooThemes framework.

It works like this:

1. You register for a Google API key, and make sure it is enabled for web fonts. Set the API key in the plugin settings menu.
2. The key is used to download the full list of Google web fonts. This is cached locally, every twelve hours at present.
3. The full list of fonts is merged into the list that the Woo framework already knows about.

What you should then see, is the ability to select any available Google web font in the WooThemes theme administration pages, and have those fonts displayed in your theme.

Register for a Google API key, and turn on "webfonts" for the key here:

[http://code.google.com/apis/console](http://code.google.com/apis/console)

This plugin has been tested against PHP5.3 and the project repository is here:

[https://github.com/academe/google-webfonts-for-woo-framework/](https://github.com/academe/google-webfonts-for-woo-framework/)

== Installation ==

1. Upload google-fonts-for-woo-framework/ to the `/wp-content/plugins/` directory or google-fonts-for-woo-framework.zip through the "Add Plugins" administration page.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add your Google API key to the settings page for the plugin.

== Frequently asked questions ==

= Where do I get a Google API key? =

A Google API key can be obtained here: http://code.google.com/apis/console

= How can I contribute to this plugin?

This plugin is managed on github here https://github.com/academe/google-webfonts-for-canvas
Feel free to raise issues there and make pull requests, as well as in the normal way on wordpress.org

= I have installed this plugin and my API key seems to be already set. How?

The option used to store your API key shares it name with several other popular Google Webfonts plugins.
If you have used, or are using, those plugins too, then the API key setting will be shared. This is by design.

== Screenshots ==

1. 
2. 

== Changelog ==



== Upgrade notice ==