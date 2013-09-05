<?php

/**
 * Description of AdClient
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdCustomer extends DataObject {
	private static $db = array(
		'Title' => 'Varchar(128)',
		'ContactEmail' => 'Text'
	);

	private static $has_many = array(
		'Campaigns' => 'AdCampaign'
	);

	private static $summary_fields = array(
		'Title',
		'ContactEmail'
	);

	public function getCMSFields(){
		$fields = parent::getCMSFields();

		$fields->push(HiddenField::create('foo'));
		return $fields;
	}
}
