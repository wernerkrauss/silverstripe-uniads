# SilverStripe Advertisement Management module

A simple module to manage advertisements on pages.

This is based on the silverstripe-advertisements module created by Marcus
Nyeholt from https://github.com/nyeholt/silverstripe-advertisements and
silverstripe-AdManager module created by Hans de Ruiter from
https://github.com/hdrlab/silverstripe-AdManager.
It will conflict with those modules, so do NOT install them.

This is fork from https://github.com/hdrlab/silverstripe-AdManager, so key
differences between de Ruiter's AdManager module and this one
are:
- This module is compatible with Silverstripe 3
- Removed all dependancies to other modules (ItemSetField)
- Default ads configuration in the Site Config removed. Ads are now shown
  depending on Start date, Expiration date and Active status on Ad and Ad Campaign.
- Ad Zones added. Now ads should be assigned to an Ad Zone.
- Advertisements can be uploaded to CMS. It can be any type of image or flash file.
- Multiple advertising banners of different sizes can be specified
- Advertisements are served at random (from within the selected campaigns or
  advertisements) based on the given Ad Zone. So it is now possible to display
  two banners with the same dimensions on the same page.

## Maintainer Contact

Elvinas Liutkevičius

<elvinas (at) unisolutions (dot) eu>

## Requirements

SilverStripe 3

## Documentation

Simply install the module using the standard method.

Note that ads are inherited hierarchically, so activating ads will mean
those ads are used across all pages unless otherwise specified in a content
tree. All existing pages will initially be set to not inherit, so you will
have to change this manually.

* Navigate to the "Ads" section
* Create some Zones
* Create some Advertisements
* If you want to group the ads in a collection, create an Ad Campaign. These in turn can be associated with a client.
* On the Advertisements tab of a page, you can select the individual ads (or campaign) to be displayed.
* In your page template, use the $DisplayAd($zone) function

	$DisplayAd(TopZone) or $DisplayAd(LeftZone)


It is possible to create an Ad Zone (subzone) with a parent Ad Zone. This
allows you to create an additional Ad Zone in page without editing the page
template. You can arrange the subzones in your way by specifying the Order value.
The ads will be shown in this way:

	Ad in Zone (if exists)
	Ad in Subzone1
	Ad in Subzone2
	...


Check the UniadsObject class for more.
