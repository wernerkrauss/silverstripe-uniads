<?php

/**
 * Class UniadsDisplayAdsTest
 *
 * Checks if $DisplayAd($zone) is working right
 */
class UniadsDisplayAdsTest extends SapphireTest {

	protected static $fixture_file = 'Uniads.yml';

	public function testActiveZone(){
		$page = $this->objFromFixture('Page', 'using-zone');

		$active = $page->getActiveZoneByTitle('ActiveZone');
		$inactive = $page->getActiveZoneByTitle('InactiveZone');

		$this->assertInstanceOf('UniadsZone', $active);
		$this->assertNull($inactive);
	}

	public function testGetSettingsPage(){
		$page = $this->objFromFixture('Page', 'using-zone');
		$settingPage = $page->getPageWithSettingsForAds();
		$this->assertEquals(1, $settingPage->ID);

		$childPage = $this->objFromFixture('Page', 'subpage');

		$childSettingPage = $childPage->getPageWithSettingsForAds(); //should return Page#1
		$this->assertEquals(1, $childSettingPage->ID);


	}

	/**
	 * Checks if getAdsByZone retrieves the right number of ads and if the returning ads are in the right zone
	 */
	public function testIfZoneIsRight()
	{
		//check correct number of Ads per Zone
		$page = $this->objFromFixture('Page', 'using-zone');
		$zone = $this->objFromFixture('UniadsZone', 'active-zone');
		$adList = $page->getAdsByZone($zone);
		$this->assertEquals(5, $adList->count());

		$pageWithExclusiveAds = $this->objFromFixture('Page', 'exclusive');
		$adList2 = $pageWithExclusiveAds->getAdsByZone($zone);
		$this->assertEquals(6, $adList2->count());

		//check if returned Ad is having the right Zone
		foreach($adList as $ad) {
			$this->assertEquals($zone->ID, $ad->ZoneID);
		}
		foreach($adList2 as $ad) {
			$this->assertEquals($zone->ID, $ad->ZoneID);
		}
	}

	public function testMaxWeight()
	{
		$page = $this->objFromFixture('Page', 'using-zone');
		$zone = $this->objFromFixture('UniadsZone', 'active-zone');
		$weight1 = $page->getMaxWeightByZone($zone);
		$this->assertEquals(4, $weight1);

		$pageWithExclusiveAds = $this->objFromFixture('Page', 'exclusive');
		$adCount = $pageWithExclusiveAds->Ads()->count();
		$this->assertEquals(1, $adCount, 'There should be one Ad in the many_many Ads on exclusive Page');

		$weight2 = $pageWithExclusiveAds->getMaxWeightByZone($zone);

		$this->assertEquals(6, $weight2);
	}


}