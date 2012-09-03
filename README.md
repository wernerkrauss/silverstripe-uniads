# SilverStripe Advertisement Management module

A simple module to manage advertisements on pages.

This is based on the silverstripe-advertisements module created by Marcus
Nyeholt from https://github.com/nyeholt/silverstripe-advertisements. It will 
conflict with that module, so do NOT install both.

The key differences between Nyeholt's advertisements module and this one
are:
- Advertisements are HTML instead of only images. This allows more freedom 
  over advertisement content, including allowing HTML 5, flash, and even 
  advertisements from external advertising services such as Google AdSense 
  and Chitika
- Multiple advertising banners of different sizes can be specified
- Advertisements are served at random (from within the selected campaigns
  or advertisements) based on the given banner size (e.g., a 160x600 
  advertising slot will only show advertisements with a size of 160x600)

## Maintainer Contact

Hans de Ruiter

<Hans (at) hdrlab (dot) org (dot) nz>

## Requirements

SilverStripe 2.4.x
ItemSetField module from http://github.com/ajshort/silverstripe-itemsetfield

## Documentation

Simply install the module using the standard method.

Note that ads are inherited hierarchically, so setting ads on the Site Config 
will mean those ads are used across all pages unless specified for a content
tree otherwise. All existing pages will initially be set to not inherit, so you will 
have to change this manually.

* Navigate to the "Ads" section
* Create some Advertisements
* If you want to group the ads in a collection, create an Ad Campaign. These in turn can be associated with a client. 
* On the Advertisements tab of a page (or Site Config), you can select the individual ads (or campaign) to be displayed. 
* In your page template, use the AdList collection to actually list out the Ads to be displayed. Use the $DisplayAd($width, $height) function

	$DisplayAd(160, 600)

Check the Advertisement class for more. 

## TODO

* Enable advertisement's HTML content to be edited using an HTML editor in addition to raw HTML (raw HTML is used right now to avoid TinyMCE's validation restrictions on items such as javascript)
* Versioning for advertisements (including having draft and published states)