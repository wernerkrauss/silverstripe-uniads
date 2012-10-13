<?php

/**
 * Description of AdvertisementExtension
 *
 * @author Hans de Ruiter <hans@hdrlab.org.nz>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdvertisementExtension extends DataObjectDecorator {
	public function extraStatics() {
		return array(
			'db'			=> array(
				'InheritSettings'	=> 'Boolean',
			),
			'defaults'		=> array(
				'InheritSettings'	=> true
			),
			'many_many'		=> array(
				'Advertisements'			=> 'Advertisement',
			),
			'has_one'		=> array(
				'UseCampaign'				=> 'AdCampaign',
			)
		);
	}
	
	public function updateCMSFields(FieldSet &$fields) {
		parent::updateCMSFields($fields);

		$fields->addFieldToTab('Root.Advertisements', new CheckboxField('InheritSettings', _t('Advertisements.INHERIT', 'Inherit parent settings')));
		$fields->addFieldToTab('Root.Advertisements', new ManyManyPickerField($this->owner, 'Advertisements'));
		$fields->addFieldToTab('Root.Advertisements', new HasOnePickerField($this->owner, 'UseCampaign'));
	}
	
	/** Displays a randomly chosen advertisement of the specified dimensions.
	 * 
	 * @param width the width of the advertisement
	 * @param height the height of the advertisement
	 */
	public function DisplayAd($width, $height) {
		$toUse = $this->owner;
		if ($toUse->InheritSettings) {
			while($toUse->ParentID) {
				if (!$toUse->InheritSettings) {
					break;
				}
				$toUse = $toUse->Parent();
			}
			if(!$toUse->ParentID && $toUse->InheritSettings)
			{
				// Using the SiteConfig's settings
				$toUse = SiteConfig::current_site_config();
			}
		}
		
		// If set to use a campaign, just switch to that as our context. 
		if ($toUse->UseCampaignID) {
			$baseWhereClause = '"CampaignID" = \'' . $toUse->UseCampaignID . 
				'\' AND "Width" = \'' . $width . '\' AND "Height" = \'' . $height . '\'';
			$ads = DataObject::get('Advertisement', $baseWhereClause . 
				' AND "Weight" >= (Rand() * (SELECT MAX(Weight) FROM "Advertisement" WHERE ' . 
				$baseWhereClause . '))', 'Rand()', '', 1);
		}
		else
		{
			$adJoin = $toUse->getManyManyJoin('Advertisements','Advertisement');
			$adFilter = $toUse->getManyManyFilter('Advertisements','Advertisement');
			$baseWhereClause = '"Width" = \'' . $width .
				'\' AND "Height" = \'' . $height . '\'';
			$ads = $toUse->getManyManyComponents('Advertisements', $baseWhereClause .
				' AND "Weight" >= (Rand() * (SELECT MAX(Weight) FROM "Advertisement" ' . $adJoin . ' WHERE ' . 
				$baseWhereClause . ' AND ' . $adFilter . '))', 'Rand()', '', 1);
		}
		
		$ad = null;
		if($ads && $ads->count() > 0) {
			$ad = $ads->First();
		}
		else {
			// Show an empty advert
			$ad = new Advertisement();
			$ad->Width = $width;
			$ad->Height = $height;
		}
		
		return $ad->forTemplate();
	}
}
