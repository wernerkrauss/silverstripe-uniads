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
		$ad = null;

		if ($zone) {
			if (!is_object($zone)) {
				$zone = UniadsZone::get()
					->filter(array(
						'Title' => $zone,
						'Active' => 1
					))
					->first();
			}
			if ($zone) {
				$toUse = $this->owner;
				if ($toUse->InheritSettings) {
					while ($toUse->ParentID) {
						if (!$toUse->InheritSettings) {
							break;
						}
						$toUse = $toUse->Parent();
					}
					if(!$toUse->ParentID && $toUse->InheritSettings) {
						$toUse = null;
					}
				}

				$UniadsObject = UniadsObject::get()->filter(array(
					'ZoneID' => $zone->ID,
					'Active' => 1
				));

				//filter for Ads not exclusively associated with a page
				//how to convert this to ORM filter?
				$UniadsObject = $UniadsObject->where('not exists (select * from Page_Ads pa where pa.UniadsObjectID = UniadsObject.ID)');

				//page specific ads, use only them
				if ($toUse) {
					$UniadsObject = $UniadsObject->where("(
						exists (select * from Page_Ads pa where pa.UniadsObjectID = UniadsObject.ID and pa.PageID = ".$toUse->ID.")
						or not exists (select * from Page_Ads pa where pa.UniadsObjectID = UniadsObject.ID)
					)");
					if ($toUse->UseCampaignID) {
						$UniadsObject = $UniadsObject->addFilter(array('CampaignID' => $toUse->UseCampaignID));
					}
				}

				$UniadsObject = $UniadsObject->leftJoin('UniAdsCampaign', 'c.ID = UniadsObject.CampaignID', 'c');

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


				$weight = rand(0, $UniadsObject->max('Weight'));

				$ad = $UniadsObject
					->where('(UniadsObject.ImpressionLimit = 0 or UniadsObject.ImpressionLimit > UniadsObject.Impressions)')
					->filter(array('Weight:GreaterThanOrEqual' => $weight))
					->sort('rand()')
					->First();

				if($ad) {
					// now we can log impression
					$conf = UniadsObject::config();
					if ($conf->record_impressions) {
						$ad->Impressions++;
						$ad->write();
					}
					if ($conf->record_impressions_stats) {
						$imp = new UniadsImpression;
						$imp->AdID = $ad->ID;
						$imp->write();
					}
				}
			}
		}

		if (!$ad) {
			// Show an empty advert
			$ad = new UniadsObject();
		}

		$output = $ad->forTemplate();

		if ($zone) {
			foreach ($zone->ChildZones()->sort('Order') as $child) {
				if ($child->Active) {
					$output .= $this->DisplayAd($child);
				}
			}
		}

		return $output;
	}

}
