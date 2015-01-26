<?php

class CWI_CHART_DataPointHelper {
	/**
	 * @param ResultSet The result to use for data generation
	 * @param string $series_name The name of the series being generated
	 * @param string $category_field The structure field/key to use to build the set of categories
	 * @param string $data_point_field The structure field/key to be used for creating the specific data point
	 */
	public static function createChartFromResultSet($result_set, $series_name, $category_field, $data_point_field) {
		FrameworkManager::loadLibrary('chart.chart');
		FrameworkManager::loadLibrary('chart.series');
		FrameworkManager::loadLibrary('chart.datapoint');
		$chart = new CWI_CHART_Chart();
		$series = new CWI_CHART_Series($series_name);
		while ($row = $result_set->getNext()) {
			$chart->addCategory($row->$category_field);
			$series->addData( new CWI_CHART_DataPoint($row->$data_point_field) );
		}
		$chart->addSeries($series);
		return $chart;
	}
	
}

?>