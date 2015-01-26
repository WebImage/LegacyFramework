<?php

class CWI_CHART_Series {
	private $name;
	private $data;
	
	function __construct($name) {
		$this->name = $name;
		$this->data = new Collection();
	}
	public function addData(CWI_CHART_IData $data) {
		$this->data->add($data);
	}
	public function getName() { return $this->name; }
	public function getNextData() { return $this->data->getNext(); }
}

?>