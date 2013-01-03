<?php

define('ADS_MODULE_DIR', basename(dirname(__FILE__)));

Director::addRules(20, array(
	'adclick//$Action/$ID' => 'AdController',
));

Object::add_extension('Page', 'AdvertisementExtension');
