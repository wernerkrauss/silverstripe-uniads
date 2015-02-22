<?php

/**
 * Description of UniadsExtension
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Hans de Ruiter <hans@hdrlab.org.nz>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsExtension extends DataExtension {

	private static $db = array(
		'InheritSettings' => 'Boolean',
	);
	private static $defaults = array(
		'InheritSettings' => true
	);
	private static $many_many = array(
		'Ads' => 'UniadsObject',
	);
	private static $has_one = array(
		'UseCampaign' => 'UniadsCampaign',
	);

	private function getListboxOptions($o) {
		$list = new DataList($o);
		return array('' => '') + $list->map()->toArray();
	}

	public function updateCMSFields(FieldList $fields) {
		parent::updateCMSFields($fields);

		$fields->findOrMakeTab('Root.Advertisements', _t('UniadsObject.PLURALNAME', 'Advertisements'));
		$fields->addFieldToTab('Root.Advertisements', new CheckboxField('InheritSettings', _t('UniadsObject.InheritSettings', 'Inherit parent settings')));

		if (!$this->owner->InheritSettings) {
			$conf = GridFieldConfig_RelationEditor::create();
			$conf->getComponentByType('GridFieldAddExistingAutocompleter')->setSearchFields(array('Title'));
			$grid = new GridField("Advertisements", _t('UniadsObject.PLURALNAME', 'Advertisements'), $this->owner->Ads(), $conf);
			$fields->addFieldToTab("Root.Advertisements", $grid);
			$fields->addFieldToTab('Root.Advertisements', new DropdownField('UseCampaignID', _t('UniadsObject.UseCampaign', 'Use Campaign'), $this->getListboxOptions('UniadsCampaign')));
		}
	}

	/** Displays a randomly chosen advertisement of the specified dimensions.
	 *
	 * @param zone of the advertisement
	 */
	public function DisplayAd($zone) {
		$output = '';
		if ($zone) {
			if (!is_object($zone)) {
				$zone = UniadsZone::getActiveZoneByTitle($zone);
			}
			if ($zone) {
				$adList = $this->getAdListForDisplaying($zone);
				foreach ($adList as $ad) {
					$output .= $ad->forTemplate();
				}
			}
		}
		return $output;
	}

	/**
	 * Gets the ad for the current zone and all subzones
	 * @param UniadsZone $zone
	 * @retunr ArrayList with all ads
	 */
	public function getAdListForDisplaying(UniadsZone $zone){
		$adList = ArrayList::create();

		$ad = $this->getRandomAdByZone($zone);

		if($ad) {
			$ad = $ad->increaseImpressions();
		}

		if (!$ad) {
			// Show an empty advert
			$ad = new UniadsObject();
		}

		$adList->add($ad);

		foreach ($zone->ChildZones()->sort('Order') as $child) {
			if ($child->Active) {
				$adList->merge($this->getAdListForDisplaying($child));
			}
		}

		return $adList;

	}

	/**
	 * Scans over the owning page and all parent pages until it finds the one with the settings for displaying ads
	 * @return null|Page
	 */
	public function getPageWithSettingsForAds()
	{
		$settingsPage = $this->owner;
		if ($settingsPage->InheritSettings) {
			while ($settingsPage->ParentID) {
				if (!$settingsPage->InheritSettings) {
					break;
				}
				$settingsPage = $settingsPage->Parent();
			}
			if (!$settingsPage->ParentID && $settingsPage->InheritSettings) {
				$settingsPage = null;
				return $settingsPage;
			}
			return $settingsPage;
		}
		return $settingsPage;
	}

	/**
	 * @param $zone
	 * @return DataList
	 */
	public function getBasicFilteredAdListByZone(UniadsZone $zone)
	{
		$adsSettingsPage = $this->getPageWithSettingsForAds();

		$UniadsObject = UniadsObject::get()->filter(
			array(
				'ZoneID' => $zone->ID,
				'Active' => 1
			)
		);



		//page specific ads, use only them
		if ($adsSettingsPage) {
			$UniadsObject = $UniadsObject->where(
				"(
						exists (select * from Page_Ads pa where pa.UniadsObjectID = UniadsObject.ID and pa.PageID = " . $adsSettingsPage->ID . ")
						or not exists (select * from Page_Ads pa where pa.UniadsObjectID = UniadsObject.ID)
					)"
			);
			if ($adsSettingsPage->UseCampaignID) {
				$UniadsObject = $UniadsObject->addFilter(array('CampaignID' => $adsSettingsPage->UseCampaignID));
			}
		} else {
			//filter for Ads not exclusively associated with a page
			//how to convert this to ORM filter?
			$UniadsObject = $UniadsObject->where(
				'not exists (select * from Page_Ads pa where pa.UniadsObjectID = UniadsObject.ID)'
			);
		}

		$UniadsObject = $UniadsObject->leftJoin('UniadsCampaign', 'c.ID = UniadsObject.CampaignID', 'c');

		//current ads and campaigns
		$campaignFilter = "(c.ID is null or (
						c.Active = '1'
						and (c.Starts <= '" . date('Y-m-d') . "' or c.Starts = '' or c.Starts is null)
						and (c.Expires >= '" . date('Y-m-d') . "' or c.Expires = '' or c.Expires is null)
					))
					and (UniadsObject.Starts <= '" . date('Y-m-d') . "' or UniadsObject.Starts = '' or UniadsObject.Starts is null)
					and (UniadsObject.Expires >= '" . date('Y-m-d') . "' or UniadsObject.Expires = '' or UniadsObject.Expires is null)
				";

		$UniadsObject = $UniadsObject->where($campaignFilter);
		$sql = $UniadsObject->sql();
		return $UniadsObject;
	}

	/**
	 * returns a DataList with all possible Ads in this zone.
	 * respects ImpressionLimit
	 *
	 * @param UniadsZone $zone
	 * @return DataList
	 */
	public function getAdsByZone(UniadsZone $zone){
		$adList = $this->getBasicFilteredAdListByZone($zone)
			->where('(UniadsObject.ImpressionLimit = 0 or UniadsObject.ImpressionLimit > UniadsObject.Impressions)');

		return $adList;
	}

	/**
	 * @param UniadsZone $zone
	 * @return UniadsObject
	 */
	public function getRandomAdByZone(UniadsZone $zone)
	{
		$weight = rand(0, $this->getMaxWeightByZone($zone));

		$randomString = DB::getConn()->random(); //e.g. rand() for mysql, random() for Sqlite3

		$ad =$this->getAdsByZone($zone)
			->filter(array('Weight:GreaterThanOrEqual' => $weight))
			->sort($randomString)
			->First();
		return $ad;
	}


	/**
	 * @param UniadsZone $zone
	 * @return string
	 */
	public function getMaxWeightByZone(UniadsZone $zone){
		$UniadsObject = $this->getBasicFilteredAdListByZone($zone);
		$weight = $UniadsObject->max('Weight');

		return $weight;
	}


}
