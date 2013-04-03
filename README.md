# Introduction

This is a plugin for WordPress. Its purpose is to pull all Google webfonts into the WooThemes Canvas
plugin - or any other plugin that uses the WooThemes framework - through the Google Developer API.

# Installation

1. Clone this repository.
2. Put the google-webfonts-for-woo-framework directory and all its contents into the archive google-webfonts-for-woo-framework.zip
3. Install the zip file into WordPres as a plugin in the normal way.

The "releases" directory holds released zip files for convenience. The latest release is also available as google-webfonts-for-woo-framework-latest.zip

# Notes

You will need a Google Developer API Key to get access to the list of fonts that Google supplies. The plugin
has a settings page that allows you to enter the API key. The plugin will refresh its list of fonts every
twelve hours at present, so there will be up to two hits on the Google API each day.

The Google Developer API Key can be obtained for free here:

http://code.google.com/apis/console

Some simple steps for obtaining the key are shown here:

http://code.garyjones.co.uk/google-developer-api-key
