<?php

/**
 * Description of AdZone
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @license BSD http://silverstripe.org/BSD-license
 */

class AdZone extends DataObject {
	public static $db = array(
		'Title' => 'Varchar',
		'ZoneWidth' => 'Varchar(6)',
		'ZoneHeight' => 'Varchar(6)',
		'Order' => 'Int',
	);

	public static $summary_fields = array(
		'Title' => 'Title',
		'ParentZone.Title' => 'Parent Zone',
		'Order' => 'Order',
	);

	public static $has_one = array(
		'ParentZone' => 'AdZone',
	);

	public static $has_many = array(
		'Advertisements' => 'Advertisement',
		'ChildZones' => 'AdZone',
	);

	public static $indexes = array(
		'Title' => true,
	);

	public static $default_records = array(
		array('Title' => 'Top', 'ZoneWidth' => '500', 'ZoneHeight' => '90'),
		array('Title' => 'Right', 'ZoneWidth' => '160', 'ZoneHeight' => '600'),
	);

	public static $default_sort = 'ParentZoneID asc, Order asc, ID asc';

	public function getWidth(){
		return $this->ZoneWidth . (ctype_digit($this->ZoneWidth) ? 'px' : '');
	}

	public function getHeight(){
		return $this->ZoneHeight . (ctype_digit($this->ZoneHeight) ? 'px' : '');
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		if (!$this->ParentZoneID) {
			$fields->removeByName('Order');
		}

		if ($this->ChildZones()->Count() > 0) {
			$fields->removeByName('ParentZoneID');
		}

		if (($field = $fields->dataFieldByName('ParentZoneID'))) {
			$field->setSource(
				DataList::create('AdZone')
				->where("ID != " . $this->ID . " and (ParentZoneID is null or ParentZoneID = 0)")
				->map()
			);
		}

		return $fields;
	}
}
