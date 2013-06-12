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
	public static $record_impressions_stats = false;
	public static $record_clicks = true;
	public static $record_clicks_stats = true;

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
		'Impressions' => 'Int',
		'Clicks' => 'Int',
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
		'Title',
	);
	public static $summary_fields = array(
		'Title',
		'Campaign.Title',
		'Zone.Title',
		'Impressions',
		'Clicks',
	);


	// for configuration
	public static function set_record_impressions($record_impressions) {
		self::$record_impressions = $record_impressions;
	}
	public static function record_impressions() {
		return self::$record_impressions;
	}
	public static function set_record_impressions_stats($record_impressions_stats) {
		self::$record_impressions_stats = $record_impressions_stats;
	}
	public static function record_impressions_stats() {
		return self::$record_impressions_stats;
	}
	public static function set_record_clicks($record_clicks) {
		self::$record_clicks = $record_clicks;
	}
	public static function record_clicks() {
		return self::$record_clicks;
	}
	public static function set_record_clicks_stats($record_clicks_stats) {
		self::$record_clicks_stats = $record_clicks_stats;
	}
	public static function record_clicks_stats() {
		return self::$record_clicks_stats;
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


	public function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);

		$labels['Campaign.Title'] = _t('Advertisement.has_one_Campaign', 'Campaign');
		$labels['Zone.Title'] = _t('Advertisement.has_one_Zone', 'Zone');
		$labels['Impressions'] = _t('Advertisement.db_Impressions', 'Impressions');
		$labels['Clicks'] = _t('Advertisement.db_Clicks', 'Clicks');

		return $labels;
	}


	public function getCMSFields() {
		$fields = new FieldList();
		$fields->push(new TabSet('Root', new Tab('Main', _t('SiteTree.TABMAIN', 'Main')
			, new TextField('Title', _t('Advertisement.db_Title', 'Title'))
		)));

		if ($this->ID) {
			$previewLink = Director::absoluteBaseURL() . 'admin/' . AdAdmin::$url_segment . '/Advertisement/preview/' . $this->ID;

			$fields->addFieldToTab('Root.Main', new ReadonlyField('Impressions', _t('Advertisement.db_Impressions', 'Impressions')), 'Title');
			$fields->addFieldToTab('Root.Main', new ReadonlyField('Clicks', _t('Advertisement.db_Clicks', 'Clicks')), 'Title');

			$fields->addFieldsToTab('Root.Main', array(
				DropdownField::create('CampaignID', _t('Advertisement.has_one_Campaign', 'Campaign'), DataList::create('AdCampaign')->map())->setEmptyString(_t('Advertisement.Campaign_none', 'none')),
				DropdownField::create('ZoneID', _t('Advertisement.has_one_Zone', 'Zone'), DataList::create('AdZone')->map())->setEmptyString(_t('Advertisement.Zone_select', 'select one')),
				new NumericField('Weight', _t('Advertisement.db_Weight', 'Weight (controls how often it will be shown relative to others)')),
				new TextField('TargetURL', _t('Advertisement.db_TargetURL', 'Target URL')),
				new Treedropdownfield('InternalPageID', _t('Advertisement.has_one_InternalPage', 'Internal Page Link'), 'Page'),
				new CheckboxField('NewWindow', _t('Advertisement.db_NewWindow', 'Open in a new Window')),
				$file = new UploadField('File', _t('Advertisement.has_one_File', 'Advertisement File')),
				$AdContent = new TextareaField('AdContent', _t('Advertisement.db_AdContent', 'Advertisement Content')),
				$Starts = new DateField('Starts', _t('Advertisement.db_Starts', 'Starts')),
				$Expires = new DateField('Expires', _t('Advertisement.db_Expires', 'Expires')),
				new NumericField('ImpressionLimit', _t('Advertisement.db_ImpressionLimit', 'Impression Limit')),
				new CheckboxField('Active', _t('Advertisement.db_Active', 'Active')),
				new LiteralField('Preview', '<a href="'.$previewLink.'" target="_blank">' . _t('Advertisement.Preview', 'Preview this advertisement') . "</a>"),
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
		if (!$this->InternalPageID && empty($this->TargetURL)) {
			return true;
		}

		$file = $this->getComponent('File');
		if ($file && $file->appCategory() == 'flash') {
			return true;
		}

		return false;
	}

	public function HaveLink() {
		return !$this->isExternalAd();
	}


	public function forTemplate() {
		$template = new SSViewer('Advertisement');
		return $template->process($this);
	}

	public function UseJSTracking() {
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
						src="'.$file->Filename.'"
						quality="high"
						width="'.$zone->Width.'"
						height="'.$zone->Height.'"
						type="application/x-shockwave-flash"
						pluginspage="http://www.macromedia.com/go/getflashplayer">
					</embed>
					</object>
				';
			} else if ($file->appCategory() == 'image') {
				return '<img src="'.$file->URL.'" style="width:100%;display:block;" alt="'.$file->Filename.'" />';
			}
		}
		return $this->AdContent;
	}

}
