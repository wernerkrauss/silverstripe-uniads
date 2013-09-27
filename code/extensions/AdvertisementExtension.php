<?php

/**
 * Description of AdvertisementExtension
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Hans de Ruiter <hans@hdrlab.org.nz>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdvertisementExtension extends DataExtension {

	public static $db = array(
		'InheritSettings' => 'Boolean',
	);
	public static $defaults = array(
		'InheritSettings' => true
	);
	public static $many_many = array(
		'Ads' => 'AdObject',
	);
	public static $has_one = array(
		'UseCampaign' => 'AdCampaign',
	);

	function getListboxOptions($o) {
		$list = new DataList($o);
		return array('' => '') + $list->map()->toArray();
	}

	public function updateCMSFields(FieldList $fields) {
		parent::updateCMSFields($fields);

		$fields->findOrMakeTab('Root.Advertisements', _t('AdObject.PLURALNAME', 'Advertisements'));
		$fields->addFieldToTab('Root.Advertisements', new CheckboxField('InheritSettings', _t('AdObject.InheritSettings', 'Inherit parent settings')));

		if (!$this->owner->InheritSettings) {
			$conf = GridFieldConfig_RelationEditor::create();
			$conf->getComponentByType('GridFieldAddExistingAutocompleter')->setSearchFields(array('Title'));
			$grid = new GridField("Advertisements", _t('AdObject.PLURALNAME', 'Advertisements'), $this->owner->Ads(), $conf);
			$fields->addFieldToTab("Root.Advertisements", $grid);
			$fields->addFieldToTab('Root.Advertisements', new DropdownField('UseCampaignID', _t('AdObject.UseCampaign', 'Use Campaign'), $this->getListboxOptions('AdCampaign')));
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
				$zone = DataObject::get_one('AdZone', "Title = '".Convert::raw2sql($zone)."' and Active = 1");
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

				$page_related = "and not exists (select * from Page_Ads pa where pa.AdObjectID = AdObject.ID)";
				$campaign = '';
				if ($toUse) {
					$page_related = "and (
						exists (select * from Page_Ads pa where pa.AdObjectID = AdObject.ID and pa.PageID = ".$toUse->ID.")
						or not exists (select * from Page_Ads pa where pa.AdObjectID = AdObject.ID)
					)";
					if ($toUse->UseCampaignID) {
						$campaign = "and c.ID = '" . $toUse->UseCampaignID . "'";
					}
				}

				$base_from = "
					AdObject
						left join AdCampaign c on c.ID = AdObject.CampaignID
				";
				$base_where = "
					AdObject.ZoneID = '" . $zone->ID . "'
					".$page_related."
					and (c.ID is null or (
						c.Active = '1'
						and (c.Starts <= '" . date('Y-m-d') . "' or c.Starts = '' or c.Starts is null)
						and (c.Expires >= '" . date('Y-m-d') . "' or c.Expires = '' or c.Expires is null)
						".$campaign."
					))
					and (AdObject.Starts <= '" . date('Y-m-d') . "' or AdObject.Starts = '' or AdObject.Starts is null)
					and (AdObject.Expires >= '" . date('Y-m-d') . "' or AdObject.Expires = '' or AdObject.Expires is null)
					and AdObject.Active = '1'
				";
				$subbase_where = preg_replace_callback(
					'/(?<!\w)(AdObject|c)\./'
					, function ($m) { return str_repeat($m[1], 2).'.'; }
					, $base_where
				);

				$sqlQuery = new SQLQuery(
					$select = 'AdObject.ID',
					$from = array($base_from),
					$where = $base_where . "
						and (AdObject.ImpressionLimit = 0 or AdObject.ImpressionLimit > AdObject.Impressions)
						and AdObject.Weight >= (rand() * (
							select max(AdObjectAdObject.Weight)
							from AdObject as AdObjectAdObject
								left join AdCampaign cc on cc.ID = AdObjectAdObject.CampaignID
							where " . $subbase_where . "
						))",
					$order = "rand()",
					$limit = 1
				);
				singleton('AdObject')->extend('augmentSQL', $sqlQuery);
				//echo $sqlQuery->sql();
				$result = $sqlQuery->execute();
				if($result && count($result) > 0) {
					$row = $result->First();
					if (isset($row['ID']) && $row['ID'] !== '') {
						$ad = DataObject::get_one('AdObject', "ID = " . $row['ID']);
						// now we can log impression
						$conf = AdObject::config();
						if ($conf->record_impressions) {
							$ad->Impressions++;
							$ad->write();
						}
						if ($conf->record_impressions_stats) {
							$imp = new AdImpression;
							$imp->AdID = $ad->ID;
							$imp->write();
						}
					}
				}
			}
		}

		if (!$ad) {
			// Show an empty advert
			$ad = new AdObject();
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
