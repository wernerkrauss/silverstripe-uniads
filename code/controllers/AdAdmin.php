<?php

/**
 * Description of AdAdmin
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdAdmin extends ModelAdmin {
	private static $managed_models = array(
		'AdObject',
		'AdCampaign',
		'AdCustomer',
		'AdZone',
	);

	private  $allowed_actions = array(
		'preview'
	);

	private static  $url_rule = '/$ModelClass/$Action/$ID/$OtherID';

	private static $url_segment = 'advrt';
	private static $menu_title = 'Ads';
	private static $menu_icon = '';


	public function __construct() {
		self::$menu_icon = ADS_MODULE_DIR . '/images/icon-advrt.png';
		parent::__construct();
	}

	/** Preview an advertisement.
	 */
	public function preview(SS_HTTPRequest $request) {
		$request->shift();
		$adID = (int) $request->param('ID');
		$ad = DataObject::get_by_id('AdObject', $adID);

		if (!$ad) {
			Controller::curr()->httpError(404);
			return;
		}

		// No impression and click tracking for previews
		$conf = AdObject::config();
		$conf->use_js_tracking = false;
		$conf->record_impressions = false;
		$conf->record_impressions_stats = false;

		// Block stylesheets and JS that are not required (using our own template)
		Requirements::clear();

		$template = new SSViewer('AdPreviewPage');

		return $template->Process($ad);
	}
}

