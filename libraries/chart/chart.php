<?php

class CWI_CHART_Chart {
	private $series;
	private $categories;
	function __construct() {
		$this->series = new Collection;
		$this->categories = new Collection;
	}
	public function addSeries($series) {
		$this->series->add($series);
	}
	public function addCategory($category) {
		$this->categories->add($category);
	}
	public function getSeries() { return $this->series; }
	public function getCategories() { return $this->categories; }
}

?>