<?php

/**
 * Description of AdAdmin
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdAdmin extends ModelAdmin {
	public static $managed_models = array(
		'Advertisement',
		'AdCampaign',
		'AdClient',
		'AdZone',
	);

	static $allowed_actions = array(
		'preview'
	);

	static $url_rule = '/$ModelClass/$Action/$ID/$OtherID';

	public static $url_segment = 'advertisements';
	public static $menu_title = 'Ads';
	public static $menu_icon = '';


	public function __construct() {
		self::$menu_icon = ADS_MODULE_DIR . '/images/ads-icon.png';
		parent::__construct();
	}

	/** Preview an advertisement.
	 */
	public function preview(SS_HTTPRequest $request)
	{
		$request->shift();
		$adID = (int) $request->param('ID');
		$ad = DataObject::get_by_id('Advertisement', $adID);

		if(!$ad)
		{
			Controller::curr()->httpError(404);
			return;
		}

		// No impression and click tracking for previews
		Advertisement::$use_js_tracking = false;
		Advertisement::$record_impressions = false;

		// Block stylesheets and JS that are not required (using our own template)
		Requirements::clear();

		$template = new SSViewer('AdPreviewPage');

		return $template->Process($ad);
	}
}
