# Introduction

This is a plugin for WordPress. Its purpose is to pull all Google webfonts into teh WooThemes Canvas
plugin through the Google Developer API.

# Installation

1. Clone this repository.
2. Put the google-webfonts-for-canvas directory and all its contents into the archive google-webfonts-for-canvas.zip
3. Install the zip file into WordPres as a plugin in the normal way.

The "releases" directory holds released zip files for convenience.

# Notes

You will need a Google Developer API Key to get access to the list of fonts that Google supplies. The plugin
has a settings page that allows you to enter the API key. The plugin will refresh its list of fonts every
twelve hours at present, so there will be up to two hits on the Google API each day.
