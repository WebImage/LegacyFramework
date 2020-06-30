<?php

define('CHARTCONTROL_TYPE_AREA', 'area');
define('CHARTCONTROL_TYPE_BAR', 'bar');
define('CHARTCONTROL_TYPE_COLUMN', 'column');
define('CHARTCONTROL_TYPE_PIE', 'pie');
define('CHARTCONTROL_TYPE_LINE', 'line');

class ChartControl extends WebControl {
	private $chart; // Chart
	private $chartType;
	function __construct($init_array=array()) {
		Page::addScript( ConfigurationManager::get('DIR_WS_GASSETS_JS') . 'fusioncharts/fusioncharts.js' );
		parent::__construct($init_array);
	}
	
	public function getChart() { return $this->chart; }
	public function getChartType() { return $this->chartType; }
	
	public function setChart(CWI_CHART_Chart $chart) { $this->chart = $chart; }
	public function setChartType($chart_type) { $this->chartType = $chart_type; }
	
	function prepareContent() {
		if (ConfigurationManager::get('SITE_ENVIRONMENT') == 'development') {
			/*
			if (is_null($this->chart)) {
				FrameworkManager::loadLogic('pagestat');
				PageStatLogic::updateUnprocessedBrowsers();
				
				$end = time();
				$start = strtotime('-6 days', $end);
				
				$filter_start = date('Y-m-d', $start);
				$filter_end = date('Y-m-d', $end);
				
				$stats = PageStatLogic::getPageViewsByDay($filter_start, $filter_end);	
	
				$chart_box_id = $this->getId() . '-rendered';
				
				$output = '<div id="' . $chart_box_id . '" align="center">Fusion Chart</div>';
				$output .= '<script type="text/javascript">';
				$output .= 'var myChart3 = new FusionCharts("' . ConfigurationManager::get('DIR_WS_GASSETS') . 'flash/fusioncharts/Area2D.swf", "whatever", "99%", "200", "0", "0");';
				$output .= 'myChart3.setDataXML("<chart ';
				$output .= " animation='1'";
				$output .= " showValues='0'";
				#$output .= " labelStep='1'";
				$output .= " divLineColor='e6f2fa'";
				$output .= " divLineThickness='1'";
				$output .= " divLineAlpha='50'";
				$output .= " vDivLineColor='e6f2fa'";
				$output .= " vDivLineThickness='1'";
				$output .= " vDivLineAlpha='50'";
				$output .= " showBorder='0'";
				$output .= " bgColor='ffffff'";
				$output .= " bgAlpha='100'";
				$output .= " showPlotBorder='1'";
				$output .= " canvasBorderAlpha='100'";
				$output .= " canvasBorderColor='e6f2fa'";
				$output .= " canvasBorderThickness='4'";
				$output .= " canvasBGColor='ffffff'";
				$output .= " drawAnchors='1'";
				$output .= " plotGradientColor=''";
				$output .= " plotBorderColor='0077cc'";
				$output .= " plotBorderThickness='4'";
				$output .= " plotFillColor='e6f2fa'";
				$output .= " plotFillAlpha='70'";
				$output .= " anchorSides='20'";
				$output .= " anchorRadius='5'";
				$output .= " anchorBorderColor='ffffff'";
				$output .= " anchorBorderThickness='2'";
				$output .= " anchorBgColor='0077cc'";
				$output .= " anchorAlpha='100'";
				$output .= " anchorBgAlpha='100'";
				$output .= ">";
				
				while ($stat = $stats->getNext()) {
					$day = strtotime($stat->day);
					$output .= '<set label=\'' . date('D', $day) . '\' value=\'' . $stat->page_views . '\' />';
				}
	
				$output .= '</chart>");';
				$output .= 'myChart3.render("' . $chart_box_id . '");';
				$output .= '</script>';
				
				$this->setRenderedContent($output);
			} else {
			*/
			if (!is_null($this->chart)) {
				$output = '';
				$chart_box_id = $this->getId() . '-rendered';
				
				$output = '<div id="' . $chart_box_id . '" align="center">Fusion Chart</div>';
				$output .= '<script type="text/javascript">';
				
				switch ($this->getChartType()) {
					case 'area':
						$chart_type = 'Area2D';
						break;
					case 'bar':
						$chart_type = 'Bar2D';
						break;
					case 'column':
						$chart_type = 'Column2D';
						break;
					case 'pie':
						$chart_type = 'Pie2D';
						break;
					case 'line':
					default:
						$chart_type = 'Line';
						break;
				}
				
				
				$output .= 'var chart_' . $this->getId() . ' = new FusionCharts("' . ConfigurationManager::get('DIR_WS_GASSETS') . 'flash/fusioncharts/' . $chart_type . '.swf", "' . $this->getId() . '", "100%", "200", "0", "0");';
				$output .= 'chart_' . $this->getId() . '.setDataXML("<chart ';
				/*
				$output .= " animation='1'";
				$output .= " showValues='0'";
				$output .= " labelStep='7'";
				$output .= " divLineColor='e6f2fa'";
				$output .= " divLineThickness='1'";
				$output .= " divLineAlpha='50'";
				$output .= " vDivLineColor='e6f2fa'";
				$output .= " vDivLineThickness='1'";
				$output .= " vDivLineAlpha='50'";
				$output .= " showBorder='0'";
				$output .= " bgColor='ffffff'";
				$output .= " bgAlpha='100'";
				$output .= " showPlotBorder='1'";
				$output .= " canvasBorderAlpha='100'";
				$output .= " canvasBorderColor='e6f2fa'";
				$output .= " canvasBorderThickness='4'";
				$output .= " canvasBGColor='ffffff'";
				$output .= " drawAnchors='1'";
				$output .= " plotGradientColor=''";
				$output .= " plotBorderColor='0077cc'";
				$output .= " plotBorderThickness='4'";
				$output .= " plotFillColor='e6f2fa'";
				$output .= " plotFillAlpha='70'";
				$output .= " anchorSides='20'";
				$output .= " anchorRadius='5'";
				$output .= " anchorBorderColor='ffffff'";
				$output .= " anchorBorderThickness='2'";
				$output .= " anchorBgColor='0077cc'";
				$output .= " anchorAlpha='100'";
				$output .= " anchorBgAlpha='100'";
				*/
				$output .= ">";
				
				$chart = $this->getChart();
				
				for ($i=0, $end=$chart->getCategories()->getCount(); $i < $end; $i++) {
					$output .= '<set label=\'' . $chart->getCategories()->getAt($i) . '\' value=\'' . $chart->getSeries()->getAt(0)->getNextData()->getPoint() . '\' />';
				}
				/*
				while ($stat = $stats->getNext()) {
					$day = strtotime($stat->day);
					$output .= '<set label=\'' . date('D', $day) . '\' value=\'' . $stat->page_views . '\' />';
				}
				*/
				$output .= '</chart>");';
				$output .= 'chart_' . $this->getId() . '.render("' . $chart_box_id . '");';
				$output .= '</script>';
				$this->setRenderedContent($output);
			}
		}
	}
}

?>