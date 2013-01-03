<?php

/**
 * Description of AdController
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdController extends Controller {

	public function clk() {
		if ($this->request->requestVar('id')) {
			$id = (int) $this->request->requestVar('id');
			if ($id) {
				$imp = new AdClick;
				$imp->AdID = $id;
				$imp->write();
			}
		}
	}

	public function go() {
		$id = (int) $this->request->param('ID');

		if ($id) {
			$ad = DataObject::get_by_id('Advertisement', $id);
			if ($ad && $ad->exists()) {
				$imp = new AdClick;
				$imp->AdID = $id;
				$imp->write();

				$this->redirect($ad->getTarget());
				return;
			}
		}
	}

}
