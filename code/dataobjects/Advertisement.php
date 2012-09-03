<?php

/**
 * Description of Advertisement
 *
 * @author Hans de Ruiter <hans@hdrlab.org.nz>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class Advertisement extends DataObject {
	
	public static $use_js_tracking = true;
	
	public static $db = array(
		'Title'				=> 'Varchar',
		'TargetURL'			=> 'Varchar(255)',
		'AdContent'			=> 'HTMLText',
		'Width'				=> 'Int',
		'Height'			=> 'Int',
	);
	
	public static $has_one = array(
		'InternalPage'		=> 'Page',
		'Campaign'			=> 'AdCampaign'
	);
	
	public static $summary_fields = array('Title');
	
	public function getCMSFields() {
		$fields = new FieldSet();
		$fields->push(new TabSet('Root', new Tab('Main', 
			new TextField('Title', 'Title'),
			new TextField('TargetURL', 'Target URL'),
			new NumericField('Width', 'Width'),
			new NumericField('Height', 'Height'),
			new TextareaField('AdContent', 'Advertisement Content', 20, 20)
		)));
		
		if ($this->ID) {
			$impressions = $this->getImpressions();
			$clicks = $this->getClicks();

			$fields->addFieldToTab('Root.Main', new ReadonlyField('Impressions', 'Impressions', $impressions), 'Title');
			$fields->addFieldToTab('Root.Main', new ReadonlyField('Clicks', 'Clicks', $clicks), 'Title');

			$previewLink = Director::absoluteBaseURL() . 'admin/' . AdAdmin::$url_segment . '/preview/' . $this->ID;
			
			$fields->addFieldsToTab('Root.Main', array(
				new Treedropdownfield('InternalPageID', 'Internal Page Link', 'Page'),
				new HasOnePickerField($this, 'Campaign', 'Ad Campaign'),
				new LiteralField('PreviewNewsletter', "<a href=\"$previewLink\" target=\"_blank\">" . _t('PREVIEWADVERTISEMENT', 'Preview this advertisement') . "</a>")
			));
		}

		return $fields;
	}
	
	protected $impressions;
	
	/** Returns true if this is an "external" advertisment (e.g., one from Google AdSense).
	 * "External" advertisements have no target URL or page.
	 */
	 protected function isExternalAd() {
	 	 if(!$this->InternalPageID && empty($this->TargetURL)) {
	 	 	 return true;
	 	 }
	 	 else {
	 	 	 return false;
	 	 }
	 }
	 
	 public function HaveLink() {
	 	 return !$this->isExternalAd();
	 }

	public function getImpressions() {
		if (!$this->impressions) {
			$query = new SQLQuery('COUNT(*) AS Impressions', 'AdImpression', '"ClassName" = \'AdImpression\' AND "AdID" = '.$this->ID);
			$res = $query->execute();
			$obj = $res->first();

			$this->impressions = 0;
			if ($obj) {
				$this->impressions = $obj['Impressions'];
			}
		}

		return $this->impressions;
	}
	
	protected $clicks;
	
	public function getClicks() {
		if (!$this->clicks) {
			if($this->isExternalAd())
			{
				return 'Not Applicable (external advertisement)';
			}
			
			$query = new SQLQuery('COUNT(*) AS Clicks', 'AdImpression', '"ClassName" = \'AdClick\' AND "AdID" = '.$this->ID);
			$res = $query->execute();
			$obj = $res->first();

			$this->clicks = 0;
			if ($obj) {
				$this->clicks = $obj['Clicks'];
			}
		}
		
		return $this->clicks;
	}
	
	public function forTemplate() {
		$template = new SSViewer('Advertisement');
		return $template->process($this);
	}
	
	public function UseJSTracking()
	{
		return self::$use_js_tracking;
	}
	
	public function Link() {
		if (self::$use_js_tracking) {
			Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery-packed.js');
			Requirements::javascript(THIRDPARTY_DIR.'/jquery-livequery/jquery.livequery.js');
			Requirements::javascript('AdManager/javascript/advertisements.js');

			$link = Convert::raw2att($this->InternalPageID ? $this->InternalPage()->AbsoluteLink() : $this->TargetURL);
			
		} else {
			$link = Controller::join_links(Director::baseURL(), 'adclick/go/'.$this->ID);
		}
		return $link;
	}
	
	public function getTarget() {
		return $this->InternalPageID ? $this->InternalPage()->AbsoluteLink() : $this->TargetURL;
	}
}
