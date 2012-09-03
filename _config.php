<?php

Director::addRules(20, array(
	'adclick//$Action/$ID' => 'AdController',
));

Object::add_extension('Page', 'AdvertisementExtension');
Object::add_extension('SiteConfig', 'AdvertisementExtension');