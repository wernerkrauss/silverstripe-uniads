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
		'InheritSettings'	=> 'Boolean',
	);
	public static $defaults = array(
		'InheritSettings'	=> true
	);
	public static $many_many = array(
		'Advertisements'			=> 'Advertisement',
	);
	public static $has_one = array(
		'UseCampaign'				=> 'AdCampaign',
	);

	function getListboxOptions($o) {
		$list = new DataList($o);
		return array('' => '') + $list->map()->toArray();
	}

	public function updateCMSFields(FieldList $fields) {
		parent::updateCMSFields($fields);

		$fields->addFieldToTab('Root.Advertisements', new CheckboxField('InheritSettings', _t('Advertisements.INHERIT', 'Inherit parent settings')));
		$conf = GridFieldConfig_RelationEditor::create();
		$conf->getComponentByType('GridFieldAddExistingAutocompleter')->setSearchFields(array('Title'));
		$grid = new GridField("Advertisements", "Advertisements", $this->owner->Advertisements(), $conf);
		$fields->addFieldToTab("Root.Advertisements", $grid);
		$fields->addFieldToTab('Root.Advertisements', new DropdownField('UseCampaignID', 'Use Campaign', $this->getListboxOptions('AdCampaign')));
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

				$page_related = "and not exists (select * from Page_Advertisements pa where pa.AdvertisementID = a.ID)";
				$campaign = '';
				if ($toUse) {
					$page_related = "and (
						exists (select * from Page_Advertisements pa where pa.AdvertisementID = a.ID and pa.PageID = ".$toUse->ID.")
						or not exists (select * from Page_Advertisements pa where pa.AdvertisementID = a.ID)
					)";
					if ($toUse->UseCampaignID) {
						$campaign = "and c.ID = '" . $toUse->UseCampaignID . "'";
					}
				}

				$base_from = "
					Advertisement as a
						left join AdCampaign c on c.ID = a.CampaignID
				";
				$base_where = "
					a.ZoneID = '" . $zone->ID . "'
					".$page_related."
					and (c.ID is null or (
						c.Active = '1'
						and (c.Starts <= '" . date('Y-m-d') . "' or c.Starts = '' or c.Starts is null)
						and (c.Expires >= '" . date('Y-m-d') . "' or c.Expires = '' or c.Expires is null)
						".$campaign."
					))
					and (a.Starts <= '" . date('Y-m-d') . "' or a.Starts = '' or a.Starts is null)
					and (a.Expires >= '" . date('Y-m-d') . "' or a.Expires = '' or a.Expires is null)
					and a.Active = '1'
				";

				$sqlQuery = new SQLQuery(
					$select = 'a.ID',
					$from = array($base_from),
					$where = $base_where . "
						and (a.ImpressionLimit = 0 or a.ImpressionLimit > (select count(i.ID) from AdImpression i where i.ClassName = 'AdImpression' and i.AdID = a.ID))
						and a.Weight >= (rand() * (
							select max(aa.Weight)
							from Advertisement as aa
								left join AdCampaign cc on cc.ID = aa.CampaignID
							where ".preg_replace('/(?<!\w)(a|c)\./e', 'str_repeat("$1", 2)."."', $base_where)."
						))",
					$order = "rand()",
					$limit = 1
				);
				//echo $sqlQuery->sql();
				$result = $sqlQuery->execute();
				if($result && count($result) > 0) {
					$row = $result->First();
					if (isset($row['ID']) && $row['ID'] !== '') {
						$ad = DataObject::get_one('Advertisement', "ID = " . $row['ID']);
						// now we can log impression
						if (Advertisement::record_impressions()) {
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
			$ad = new Advertisement();
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
