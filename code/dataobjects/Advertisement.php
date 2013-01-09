<?php

/**
 * Description of Advertisement
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Hans de Ruiter <hans@hdrlab.org.nz>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class Advertisement extends DataObject {

	public static $use_js_tracking = true;
	public static $record_impressions = true;

	public static $files_dir = 'Ads';
	public static $max_file_size = 2097152;

	public static $db = array(
		'Title' => 'Varchar',
		'Starts' => 'Date',
		'Expires' => 'Date',
		'Active' => 'Boolean',
		'TargetURL' => 'Varchar(255)',
		'NewWindow' => 'Boolean',
		'AdContent' => 'HTMLText',
		'ImpressionLimit' => 'Int',
		'Weight' => 'Double',
	);

	public static $has_one = array(
		'File' => 'File',
		'Zone' => 'AdZone',
		'Campaign' => 'AdCampaign',
		'InternalPage' => 'Page',
	);

	public static $belongs_many_many = array(
		'AdInPages' => 'Page',
	);


	public static $defaults = array(
		'Active' => 0,
		'NewWindow' => 1,
		'ImpressionLimit' => 0,
		'Weight' => 1.0,
	);
	public static $searchable_fields = array(
		'Title' => 'Title',
	);
	public static $summary_fields = array(
		'Title' => 'Title',
		'Campaign.Title' => 'Campaign',
		'Zone.Title' => 'Zone',
		'Clicks' => 'Clicks',
		'Impressions' => 'Impressions',
	);


	// for configuration
	public static function set_record_impressions($record_impressions) {
		self::$record_impressions = $record_impressions;
	}
	public static function record_impressions() {
		return self::$record_impressions;
	}
	public static function set_files_dir($files_dir) {
		self::$files_dir = $files_dir;
	}
	public static function files_dir() {
		return self::$files_dir;
	}
	public static function set_max_file_size($max_file_size) {
		self::$max_file_size = $max_file_size;
	}
	public static function max_file_size() {
		return self::$max_file_size;
	}


	public function getCMSFields() {
		$fields = new FieldList();
		$fields->push(new TabSet('Root', new Tab('Main',
			new TextField('Title', 'Title')
		)));

		if ($this->ID) {
			$impressions = $this->getImpressions();
			$clicks = $this->getClicks();
			$previewLink = Director::absoluteBaseURL() . 'admin/' . AdAdmin::$url_segment . '/Advertisement/preview/' . $this->ID;

			$fields->addFieldToTab('Root.Main', new ReadonlyField('Impressions', 'Impressions', $impressions), 'Title');
			$fields->addFieldToTab('Root.Main', new ReadonlyField('Clicks', 'Clicks', $clicks), 'Title');

			$fields->addFieldsToTab('Root.Main', array(
				DropdownField::create('CampaignID', 'Campaign', DataList::create('AdCampaign')->map())->setEmptyString(' '),
				DropdownField::create('ZoneID', 'Zone', DataList::create('AdZone')->map())->setEmptyString(' '),
				new NumericField('Weight', 'Weight (controls how often it will be shown relative to others)'),
				new TextField('TargetURL', 'Target URL'),
				new Treedropdownfield('InternalPageID', 'Internal Page Link', 'Page'),
				new CheckboxField('NewWindow', 'Open in a new Window'),
				$file = new UploadField('File', 'Advertisement File'),
				$AdContent = new TextareaField('AdContent', 'Advertisement Content'),
				$Starts = new DateField('Starts', 'Starts'),
				$Expires = new DateField('Expires', 'Expires'),
				new NumericField('ImpressionLimit', 'Impression Limit'),
				new CheckboxField('Active', 'Active'),
				new LiteralField('PreviewNewsletter', "<a href=\"$previewLink\" target=\"_blank\">" . _t('PREVIEWADVERTISEMENT', 'Preview this advertisement') . "</a>"),
			));

			$file->setFolderName(self::files_dir());
			$file->getValidator()->setAllowedMaxFileSize(array('*' => self::max_file_size()));
			$file->getValidator()->setAllowedExtensions(array_merge(File::$app_categories['image'], File::$app_categories['flash']));

			$AdContent->setRows(10);
			$AdContent->setColumns(20);

			$Starts->setConfig('showcalendar', true);
			$Starts->setConfig('dateformat', i18n::get_date_format());
			$Starts->setConfig('datavalueformat', 'yyyy-MM-dd');

			$Expires->setConfig('showcalendar', true);
			$Expires->setConfig('dateformat', i18n::get_date_format());
			$Expires->setConfig('datavalueformat', 'yyyy-MM-dd');
			$Expires->setConfig('min', date('Y-m-d', strtotime($this->Starts ? $this->Starts : '+1 days')));
		}

		return $fields;
	}


	/** Returns true if this is an "external" advertisment (e.g., one from Google AdSense).
	 * "External" advertisements have no target URL or page.
	 */
	 protected function isExternalAd() {
	 	 if(!$this->InternalPageID && empty($this->TargetURL)) {
	 	 	 return true;
	 	 } else {
	 	 	 return false;
	 	 }
	 }

	 public function HaveLink() {
	 	 return !$this->isExternalAd();
	 }

	protected $impressions;
	public function getImpressions() {
		if (!$this->impressions) {
			$query = new SQLQuery(array('Impressions' => 'count(*)'), 'AdImpression', "ClassName = 'AdImpression' and AdID = ".$this->ID);
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

			$query = new SQLQuery(array('Clicks' => 'count(*)'), 'AdImpression', "ClassName = 'AdClick' and AdID = ".$this->ID);
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
			Requirements::javascript(ADS_MODULE_DIR.'/javascript/advertisements.js');

			$link = Convert::raw2att($this->getTarget());
		} else {
			$link = Controller::join_links(Director::baseURL(), 'adclick/go/'.$this->ID);
		}
		return $link;
	}

	public function getTarget() {
		return $this->InternalPageID
			? $this->InternalPage()->AbsoluteLink()
			: (strpos($this->TargetURL, 'http') !== 0 ? 'http://' : '') . $this->TargetURL
		;
	}

	public function getContent() {
		$file = $this->getComponent('File');
		$zone = $this->getComponent('Zone');
		if ($file) {
			if ($file->appCategory() == 'flash') {
				return '
					<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="'.$zone->Width.'" height="'.$zone->Height.'">
					<param name="movie" value="'.$file->Filename.'" />
					<param name="quality" value="high" />
					<embed
						src="'.$this->File->Filename.'"
						quality="high"
						width="'.$zone->Width.'"
						height="'.$zone->Height.'"
						type="application/x-shockwave-flash"
						pluginspage="http://www.macromedia.com/go/getflashplayer">
					</embed>
					</object>
				';
			} else if ($file->appCategory() == 'image') {
				return '<img src="'.$file->URL.'" style="width:100%" alt="'.$file->Filename.'" />';
			}
		}
		return $this->AdContent;
	}

}
