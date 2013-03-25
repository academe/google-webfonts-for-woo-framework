The purpose of this plugin is to make all available Google webfonts available to 
the WooThemes Canvas theme.

Contact:

Jason Judge <jason.judge@academe.co.uk>

Project page:

https://github.com/academe/google-webfonts-for-canvas/

It works like this:

1. You register for a Google API key, and make sure it is enabled for web fonts.
   Set the API key in the plugin settings menu.
2. The key is used to download the full list of of Google web fonts. This is
   cached locally.
3. The full list of fonts is merged into the list that the Canvas theme already
   knows about.

What you should then see, is the ability to select any available Google web font
in the Canvas administration pages, and have those fonts displayed in your Canvas
theme.

Requires:

PHP >= 5.3
Coding style: PSR-1 and PSR-2 as much as possible.
Tested against WordPress 3.5.1
Tested against Canvas 5.1.5 with framework 5.5.5

This is a quick, dirty, and rough first draft. Additional features can be added later,
but for now it is there to test and use, and should be suitable for production.

The only thingto be aware of, is that the Google fonts are cached for 12 hours in the
WP installation. A fresh fetch will then be made from Google. You must ensure the API
key is always valid.
