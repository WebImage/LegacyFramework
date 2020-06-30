<?php

/**
 * Do startup checks - need to incorporate plugins as possible options
 **/
FrameworkManager::loadLibrary('event.manager');

class CWI_EVENT_StartupArgs extends CWI_EVENT_Args {
	private $messages;
	function __construct() {
		$this->messages = new Collection();
	}
	public function addMessage($message) {
		$this->messages->add($message);
	}
	public function getMessages() { return $this->messages; }
}

function cwi_check_dir(CWI_EVENT_StartupArgs $args, $dir, $name, $description) {
	
	$format = '%s %s.  Purpose: %s.  Location: %s';
	if (file_exists($dir)) {
		if (!is_writable($dir)) {
			$args->addMessage(sprintf($format, $name, 'is not writable', $description, $dir));
		}
	} else {
		$args->addMessage(sprintf($format, $name, 'does not exist', $description, $dir));
	}
	
}
function cwi_dashboard_messages(CWI_EVENT_Event $event, CWI_EVENT_StartupArgs $args) {

	$config = ConfigurationManager::getConfig();

	/**
	 * Check temp directory
	 **/
	$dir = ConfigurationManager::get('DIR_FS_TMP');
	cwi_check_dir($args, $dir, 'Temporary', 'Used internally as a temporary working space');
	
	$dir = ConfigurationManager::get('DIR_FS_CACHE');
	cwi_check_dir($args, $dir, 'Cache', 'Used internally to speed up the loading of certain files');
	
	$dir = ConfigurationManager::get('DIR_FS_ASSETS');
	cwi_check_dir($args, $dir, 'Assets', 'Used to store uploaded files');
	
	$errors = $args->getMessages();
	while ($error = $errors->getNext()) {
		ErrorManager::addError($error);
	}
}

CWI_EVENT_Manager::listenForGeneral('adminDashboardMessagesDisplaying', 'cwi_dashboard_messages');

$args = new CWI_EVENT_StartupArgs();
CWI_EVENT_Manager::triggerGeneral('adminDashboardMessagesDisplaying', $args);

/*
FrameworkManager::loadLogic('pagestat');
PageStatLogic::updateUnprocessedBrowsers();


#if (ConfigurationManager::get('SITE_ENVIRONMENT') == 'development') {
#	$end = time();
#	$start = strtotime('-7 days', $end);
#	
#	$filter_start = date('Y-m-d', $start);
#	$filter_end = date('Y-m-d', $end);
#	
#	$stats = PageStatLogic::getPageViewsByDay($filter_start, $filter_end);	
#}
*/

if ($admin_main_output = Page::getControlById('admin_main_output')) {
	/*
	if (ConfigurationManager::get('SITE_ENVIRONMENT') == 'development') {
		$main_content = Page::getControlById('main-content');
		$main_content->visible(false);
		
		$ph_admin_main = Page::getControlById('ph_admin_main');
		
		// Add text description
		$literal = new LiteralControl();
		$literal->setText('Welcome to AthenaWMS, the website management software that allows you to edit your website with ease.  Please select an option from the menus above to begin editing your site.');
		$admin_main_output->addControl($literal);
		$admin_main_output->visible(false);
		$ph_admin_main->addControl($admin_main_output);
		
		
		FrameworkManager::loadControl('chart');
		
		FrameworkManager::loadLibrary('chart.datapointhelper');
		
		$end = time();
		$start = strtotime('-6 days', $end);
		
		######### PAGE VIEW TREND #############
		
		function admin_home_generate_panel($title, $body_control) {
			$panel = new WrapOutputControl();
			$panel_header = new WrapOutputControl();
			$panel_title = new WrapOutputControl();
			$panel_body = new WrapOutputControl();
			
			$panel->setWrapClassId('panel');
			$panel_header->setWrapClassId('panel-header');
			$panel_title->setWrapClassId('panel-title');
			$panel_body->setWrapClassId('panel-body');
			
			$lbl_panel_title = new LiteralControl();
			$lbl_panel_title->setText($title);
			$panel_title->addControl($lbl_panel_title);
			
			$panel_header->addControl($panel_title);
			$panel->addControl($panel_header);
			$panel->addControl($panel_body);
			
			$panel_body->addControl($body_control);
			
			return $panel;
		}
		
		$filter_start = date('Y-m-d', $start);
		$filter_end = date('Y-m-d', $end);
		
		########### PAGE VIEW TREND ##############

		$stats = PageStatLogic::getPageViewsByDay($filter_start, $filter_end);

		while ($stat = $stats->getNext()) {
			$stat->day = date('M d', strtotime($stat->day));
		}
		$chart_data = CWI_CHART_DataPointHelper::createChartFromResultSet($stats, 'Pages Views per Day', 'day', 'page_views');

		$chart_control = new ChartControl();
		$chart_control->setId('page_views');
		$chart_control->setChartType(CHARTCONTROL_TYPE_AREA);
		$chart_control->setChart($chart_data);
		
		$pages_panel = admin_home_generate_panel('Page View Trend', $chart_control);
		$ph_admin_main->addControl($pages_panel);

		########### BROWSER STATS #############
		
		#$browser_stats = PageStatLogic::getTopBrowsersForPeriod($filter_start, $filter_end);
		#$browser_chart_data = CWI_CHART_DataPointHelper::createChartFromResultSet($browser_stats, 'Top Browsers', 'label', 'cnt');
		
		#$chart_control = new ChartControl();
		#$chart_control->setId('browser-stats');
		#$chart_control->setChartType(CHARTCONTROL_TYPE_PIE);
		#$chart_control->setChart($browser_chart_data);
		
		#$browser_panel = admin_home_generate_panel('Browser Usage', $chart_control);
		
		#$ph_admin_main->addControl($browser_panel);
		
		########### TOP PAGES #############
		
		$top_page_stats = PageStatLogic::getTopPageViewsForPeriod($filter_start, $filter_end);
		while ($stat = $top_page_stats->getNext()) {
			$stat->label = 'test';
		}
		$top_page_chart = CWI_CHART_DataPointHelper::createChartFromResultSet($top_page_stats, 'Top Pages', 'label', 'cnt');
		
		$chart_control = new ChartControl();
		$chart_control->setId('top_pages');
		$chart_control->setChartType(CHARTCONTROL_TYPE_COLUMN);
		$chart_control->setChart($top_page_chart);
		
		$top_pages_panel = admin_home_generate_panel('Top Pages', $chart_control);
		
		$ph_admin_main->addControl($top_pages_panel);
		
	} else {
		*/
	
		$literal = new LiteralControl();
		$literal->setText('Welcome to AthenaWMS, the website management software that allows you to edit your website with ease.  Please select an option from the menus above to begin editing your site.');
		$admin_main_output->addControl($literal);
		
		/*
	}
	*/
}

?>