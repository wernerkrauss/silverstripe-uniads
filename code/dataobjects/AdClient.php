<?php

/**
 * Description of AdClient
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdClient extends DataObject {
	public static $db = array(
		'Title' => 'Varchar(128)',
		'ContactEmail' => 'Text',
	);
	public static $summary_fields = array(
		'Title',
		'ContactEmail',
	);
}
