=== Google Webfonts For Woo Framework ===
Contributors: judgej
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=B4STZL8F5WHK6
Tags: woothemes, google webfonts, typography, fonts, woo framework
Requires at least: 3.3
Tested up to: 3.7.1
Stable tag: 1.3.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Give the WooThemes framework access to the full range of current Google Webfonts.

== Description ==

The purpose of this plugin is to make all available Google webfonts available to the WooThemes Canvas theme,
and any other themes that use the WooThemes framework. It also allows additional selected international subsets
(Greek, Cyrillic, Vietnamese etc.) to loaded, which the Woo Framework does not support at present.

It works like this:

1. You register for a Google API key, and make sure it is enabled for web fonts. Set the API key in the plugin settings menu.
2. The key is used to download the full list of Google web fonts. This is cached locally, and will only be refreshed when you save the plugin settings page.
3. The full list of fonts and all variants replaces the list that the Woo framework defines internally.

What you should then see, is the ability to select any available Google web font in the WooThemes theme administration pages, and have those fonts displayed in your theme.

To get the absolute latest fonts from Google, register for a Google API key, and turn on the "webfonts" API for that key here:

[http://code.google.com/apis/console](http://code.google.com/apis/console)

However, this plugin has a fallback list of fonts, so you can try it out without an API key, and that might even be
good enough for your purposes.

This plugin has been tested against PHP5.3 and the project repository is here:

[https://github.com/academe/google-webfonts-for-woo-framework/](https://github.com/academe/google-webfonts-for-woo-framework/)

Changes have been made so that it works with PHP5.2 and has been reported as working.
However, I work under at least 5.3 so some incompatibilities may creep in by accident from time-to-time - just report them and I will do my best to fix as quickly as possible.

Please let me know how this plugin works for you, whether you like it, and how it can be improved.

== Installation ==

1. Upload google-fonts-for-woo-framework/ to the `/wp-content/plugins/` directory or google-fonts-for-woo-framework.zip through the "Add Plugins" administration page.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add your Google API key to the settings page for the plugin.

== Frequently asked questions ==

= What if I don't have a Google API key? =

No problem! From version 1.2.0 this plugin carries a list of fonts and variants as a fallback. If the API key is not
entered or the API key is invalid, then the plugin will use the fallback list. This list initially contains 652 fonts
but will slowly get out of date as Google extends its fonts. It will be refreshed on each new version of the plugin
however.

= Where do I get a Google API key? =

A Google API key can be obtained here: https://cloud.google.com/console?redirected=true

= How can I contribute to this plugin? =

This plugin is managed on github here https://github.com/academe/google-webfonts-for-woo-framework
Feel free to raise issues there and make pull requests, as well as in the normal way on wordpress.org

= I have installed this plugin and my API key seems to be already set. How? =

The option used to store your API key shares it name with several other popular Google Webfonts plugins.
If you have used, or are using, those plugins too, then the API key setting will be shared. This is by design.

= I see errors reported when fetching the new Google webfonts =

Some hosts may not be set up to allow your site to fetch data from remote sites. Please report any 
such errors on the plugin page or github, and we will try looking for a workaround.

= Can I use the new fonts in a page body? =

Yes, you can. WooThemes themes provides a short-code tag that allows you to embed any font in the body of a page or post.
However, the list of fonts that the short-code quick-create function that the content editor provides, will not
include the full list of fonts. To work around this, just select any random font to create a short-code tag,
then change the name of the font manually in that tag. The theme will load that font automatically when you display
the page or post.

= Tell me about variants. What are they? =

Many Google Webfonts come with variants, and these are listed against each font in the plugin settings page.
A variant is another version font from the same font family, that is used for displaying a different style
or weight. Where a variant does not exist, for example if there is no "italic" variant, the web browser will
fake it and make a best guess of what it would look like. This is not ideal from a design perspective.
So where possible, use fonts that have variants listed that cover the styles and weights that you want to use
in your design. That way you will get a font that has been carefully designed for that purpose.

The settings page allows you to preview specific variants of each font, as well as the standard core font
(generally normal/non-italic, and regular/400-weight).

= Is it just Google Webfonts? =

For now, yes it is. The Woo Framework is designed to pull in external webfonts from Google only at present.

There are plans to extend this to other sources of web-based fonts, with the risk that it may be a little 
ess robust when it comes to theme updates from WooThemes. However, that will likely be a separate plugin;
this plugin will continue to support just Google Webfonts.

= How can I get a list of Google fonts this plugin recognises? =

If you do not supply a valid API key, this plugin will fall back to the fonts listed in fonts.json
To get an updated list of fonts for that file, make sure you have a valid API key then add this
parameter to the end of the settings page for this plugin:

    export=1

Your URL will look like this:

    http://yoursite.example.com/wp-admin/options-general.php?page=gw-for-wooframework&export=1

This will then list all the fonts and their variants downloaded from the Google API in the same
format that fonts.json uses. There is no API at this time for fetching the subsets (latin, greek etc) or font
classes (serif, sans serif, handwriting, etc), so that information is not included. However, if you are 
interested in those details, I'm trying to keep an updated list here:

https://github.com/academe/GoogleFontMetadata

== Screenshots ==

1. 
2. 

== Changelog ==

= Version 1.3.3 =
* New fontsin fallback  list: Alegreya Sans, Alegreya Sans SC, Exo 2, Kantumruy, Kdam Thmor plus "normal" weight for Sniglet.

= Version 1.3.2 =
* An official release of 1.3.1 with some minor text amendments.

= Version 1.3.1 (beta) =
* Support Google's international subsets. Select which subsets you want to load for all used fonts.

= Version 1.2.3 =
* Check for missing object during activation, to skip warning.

= Version 1.2.2 =
* Fixed bug that caused failure in fallback fonts to load.

= Version 1.2.1 =
* Version tags corrected, but otherwise same as 1.2.0

= Version 1.2.0 =
* Introduction of fallback list of fonts when Google cannot be contacted for any reason.
* Cache is now indefinite; it is refreshed only when saving the settings page.
* The list of fonts from Google completely replaces the list in the Woo Framework, rather than merging in.
* The font list shown on the settings page shows the available variants more clearly.
* The font preview function supports italic style and the main font weights (nine weights from 100 to 900).

= Version 1.1.0 =
* Reports errors fetching Google fonts.
* Updates the built-in framework fonts with new variations, improving quality of displayed fonts.

= Version 1.0.0 =
* i18n complete.
* Show fonts used by the theme as selected in admin page.
* Split admin functions into a separate script and class.
* Ability to preview fonts.

== Upgrade notice ==
