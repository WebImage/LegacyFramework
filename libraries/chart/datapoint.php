<?php

FrameworkManager::loadLibrary('chart.idata');

class CWI_CHART_DataPoint implements CWI_CHART_IData {
	private $value;//, $label, $link;
	function __construct($point) {
		$this->point = $point;
	}
	function getPoint() { return $this->point; }
}

?>