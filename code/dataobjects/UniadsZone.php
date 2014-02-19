<?php

/**
 * Description of UniadsZone
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @license BSD http://silverstripe.org/BSD-license
 */

class UniadsZone extends DataObject {
	public static $db = array(
		'Title' => 'Varchar',
		'ZoneWidth' => 'Varchar(6)',
		'ZoneHeight' => 'Varchar(6)',
		'Order' => 'Int',
		'Active' => 'Boolean',
	);

	public static $summary_fields = array(
		'Title',
		'ParentZone.Title',
		'Order',
		'Active',
	);

	public static $has_one = array(
		'ParentZone' => 'UniadsZone',
	);

	public static $has_many = array(
		'Ads' => 'UniadsObject',
		'ChildZones' => 'UniadsZone',
	);

	public static $indexes = array(
		'Title' => true,
	);

	public static $defaults = array(
		'Active' => 1,
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

	function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);

		$labels['ParentZone.Title'] = _t('UniadsZone.has_one_ParentZone', 'Parent Zone');

		return $labels;
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
				DataList::create('UniadsZone')
				->where("ID != " . $this->ID . " and (ParentZoneID is null or ParentZoneID = 0)")
				->map()
			);
		}

		return $fields;
	}
}
