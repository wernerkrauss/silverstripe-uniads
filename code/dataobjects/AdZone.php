<?php

/**
 * Description of AdZone
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @license BSD http://silverstripe.org/BSD-license
 */

class AdZone extends DataObject {
	private static $db = array(
		'Title' => 'Varchar',
		'ZoneWidth' => 'Varchar(6)',
		'ZoneHeight' => 'Varchar(6)',
		'Order' => 'Int',
		'Active' => 'Boolean',
	);

	private static $summary_fields = array(
		'Title',
		'ParentZone.Title',
		'Order',
		'Active',
	);

	private static $has_one = array(
		'ParentZone' => 'AdZone',
	);

	private static $has_many = array(
		'Ads' => 'AdObject',
		'ChildZones' => 'AdZone',
	);

	private static $indexes = array(
		'Title' => true,
	);

	private static $defaults = array(
		'Active' => 1,
	);

	private static $default_records = array(
		array('Title' => 'Top', 'ZoneWidth' => '500', 'ZoneHeight' => '90'),
		array('Title' => 'Right', 'ZoneWidth' => '160', 'ZoneHeight' => '600'),
	);

	private static $default_sort = 'ParentZoneID asc, Order asc, ID asc';

	public function getWidth(){
		return $this->ZoneWidth . (ctype_digit($this->ZoneWidth) ? 'px' : '');
	}

	public function getHeight(){
		return $this->ZoneHeight . (ctype_digit($this->ZoneHeight) ? 'px' : '');
	}

	function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);

		$labels['ParentZone.Title'] = _t('AdZone.has_one_ParentZone', 'Parent Zone');

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
				DataList::create('AdZone')
				->where("ID != " . $this->ID . " and (ParentZoneID is null or ParentZoneID = 0)")
				->map()
			);
		}

		return $fields;
	}
}
