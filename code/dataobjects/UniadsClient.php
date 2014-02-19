<?php

/**
 * Description of UniadsClient
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsClient extends DataObject {
	public static $db = array(
		'Title' => 'Varchar(128)',
		'ContactEmail' => 'Text',
	);
	public static $summary_fields = array(
		'Title',
		'ContactEmail',
	);
}
