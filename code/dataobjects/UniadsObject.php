<?php

/**
 * Description of UniadsObject (ddvertisement object)
 *
 * @author Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @author Hans de Ruiter <hans@hdrlab.org.nz>
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class UniadsObject extends DataObject {

	private static $use_js_tracking = true;
	private static $record_impressions = true;
	private static $record_impressions_stats = false;
	private static $record_clicks = true;
	private static $record_clicks_stats = true;

	private static $files_dir = 'UploadedAds';
	private static $max_file_size = 2097152;

	private static $db = array(
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

	private static $has_one = array(
		'File' => 'File',
		'Zone' => 'UniadsZone',
		'Campaign' => 'UniadsCampaign',
		'InternalPage' => 'Page',
	);

	private static $belongs_many_many = array(
		'AdInPages' => 'Page',
	);


	private static $defaults = array(
		'Active' => 0,
		'NewWindow' => 1,
		'ImpressionLimit' => 0,
		'Weight' => 1.0,
	);
	private static $searchable_fields = array(
		'Title',
	);
	private static $summary_fields = array(
		'Title',
		'Campaign.Title',
		'Zone.Title',
		'Impressions',
		'Clicks',
	);


	public function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);

		$labels['Campaign.Title'] = _t('UniadsObject.has_one_Campaign', 'Campaign');
		$labels['Zone.Title'] = _t('UniadsObject.has_one_Zone', 'Zone');
		$labels['Impressions'] = _t('UniadsObject.db_Impressions', 'Impressions');
		$labels['Clicks'] = _t('UniadsObject.db_Clicks', 'Clicks');

		return $labels;
	}


	public function getCMSFields() {
		$fields = new FieldList();
		$fields->push(new TabSet('Root', new Tab('Main', _t('SiteTree.TABMAIN', 'Main')
			, new TextField('Title', _t('UniadsObject.db_Title', 'Title'))
		)));

		if ($this->ID) {
			$previewLink = Director::absoluteBaseURL() . 'admin/' . UniadsAdmin::config()->url_segment . '/UniadsObject/preview/' . $this->ID;

			$fields->addFieldToTab('Root.Main', new ReadonlyField('Impressions', _t('UniadsObject.db_Impressions', 'Impressions')), 'Title');
			$fields->addFieldToTab('Root.Main', new ReadonlyField('Clicks', _t('UniadsObject.db_Clicks', 'Clicks')), 'Title');

			$fields->addFieldsToTab('Root.Main', array(
				DropdownField::create('CampaignID', _t('UniadsObject.has_one_Campaign', 'Campaign'), DataList::create('UniadsCampaign')->map())->setEmptyString(_t('UniadsObject.Campaign_none', 'none')),
				DropdownField::create('ZoneID', _t('UniadsObject.has_one_Zone', 'Zone'), DataList::create('UniadsZone')->map())->setEmptyString(_t('UniadsObject.Zone_select', 'select one')),
				new NumericField('Weight', _t('UniadsObject.db_Weight', 'Weight (controls how often it will be shown relative to others)')),
				new TextField('TargetURL', _t('UniadsObject.db_TargetURL', 'Target URL')),
				new Treedropdownfield('InternalPageID', _t('UniadsObject.has_one_InternalPage', 'Internal Page Link'), 'Page'),
				new CheckboxField('NewWindow', _t('UniadsObject.db_NewWindow', 'Open in a new Window')),
				$file = new UploadField('File', _t('UniadsObject.has_one_File', 'Advertisement File')),
				$AdContent = new TextareaField('AdContent', _t('UniadsObject.db_AdContent', 'Advertisement Content')),
				$Starts = new DateField('Starts', _t('UniadsObject.db_Starts', 'Starts')),
				$Expires = new DateField('Expires', _t('UniadsObject.db_Expires', 'Expires')),
				new NumericField('ImpressionLimit', _t('UniadsObject.db_ImpressionLimit', 'Impression Limit')),
				new CheckboxField('Active', _t('UniadsObject.db_Active', 'Active')),
				new LiteralField('Preview', '<a href="'.$previewLink.'" target="_blank">' . _t('UniadsObject.Preview', 'Preview this advertisement') . "</a>"),
			));

			$app_categories = File::config()->app_categories;
			$file->setFolderName($this->config()->files_dir);
			$file->getValidator()->setAllowedMaxFileSize(array('*' => $this->config()->max_file_size));
			$file->getValidator()->setAllowedExtensions(array_merge($app_categories['image'], $app_categories['flash']));

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

		$this->extend('updateCMSFields', $fields);
		return $fields;
	}


	/** Returns true if this is an "external" advertisment (e.g., one from Google AdSense).
	 * "External" advertisements have no target URL or page.
	 */
	public function ExternalAd() {
		if (!$this->InternalPageID && empty($this->TargetURL)) {
			return true;
		}

		$file = $this->getComponent('File');
		if ($file && $file->appCategory() == 'flash') {
			return true;
		}

		return false;
	}

	public function forTemplate() {
		$template = new SSViewer('UniadsObject');
		return $template->process($this);
	}

	public function UseJsTracking() {
		return $this->config()->use_js_tracking;
	}

	public function TrackingLink($absolute = false) {
		return Controller::join_links($absolute ? Director::absoluteBaseURL() : Director::baseURL(), 'uniads-click/go/'.$this->ID);
	}

	public function Link() {
		if ($this->UseJsTracking()) {
			Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.js'); // TODO: How about jquery.min.js?
			Requirements::javascript(ADS_MODULE_DIR.'/javascript/uniads.js');

			$link = Convert::raw2att($this->getTarget());
		} else {
			$link = $this->TrackingLink();
		}
		return $link;
	}

	public function getTarget() {
		return $this->InternalPageID
			? $this->InternalPage()->AbsoluteLink()
			: ($this->TargetURL ? (strpos($this->TargetURL, 'http') !== 0 ? 'http://' : '') . $this->TargetURL : false)
		;
	}

	public function getContent() {
		$file = $this->getComponent('File');
		$zone = $this->getComponent('Zone');
		if ($file) {
			if ($file->appCategory() == 'flash') {
				$src = $this->getTarget() ? HTTP::setGetVar('clickTAG', $this->TrackingLink(true), $file->Filename) : $file->Filename;
				return '
					<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="'.$zone->Width.'" height="'.$zone->Height.'" style="display:block;">
						<param name="movie" value="'.$src.'" />
						<param name="quality" value="high" />
						<param name="wmode" value="transparent" />
						<embed
							src="'.$src.'"
							quality="high"
							wmode="transparent"
							width="'.$zone->Width.'"
							height="'.$zone->Height.'"
							type="application/x-shockwave-flash"
							pluginspage="http://www.macromedia.com/go/getflashplayer">
						</embed>
					</object>
				';
			} else if ($file->appCategory() == 'image') {
				return '<img src="'.$file->URL.'" style="width:100%;display:block;" alt="'.$file->Title.'" />';
			}
		}
		return $this->AdContent;
	}

}
