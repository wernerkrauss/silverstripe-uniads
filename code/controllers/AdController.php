<?php

/**
 * Description of AdController
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdController extends Controller {

	private static $allowed_actions = array(
		'clk',
		'go'
	);

	public function clk() {
		$this->GetAdAndLogClick($this->request->requestVar('id'));
	}

	public function go() {
		$ad = $this->GetAdAndLogClick($this->request->param('ID'));
		if ($ad) {
			$this->redirect($ad->getTarget());
		}
	}

	private function GetAdAndLogClick($id) {
		$id = (int) $id;
		if ($id) {
			$ad = DataObject::get_by_id('AdObject', $id);
			if ($ad && $ad->exists()) {
				$conf = AdObject::config();
				if ($conf->record_clicks) {
					$ad->Clicks++;
					$ad->write();
				}
				if ($conf->record_clicks_stats) {
					$clk = new AdClick;
					$clk->AdID = $ad->ID;
					$clk->write();
				}
				return $ad;
			}
		}
		return null;
	}

}
