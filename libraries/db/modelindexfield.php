<?php

class CWI_DB_ModelIndexField {
	private $name, $length;
	#$direction; // Direction would be asc or desc.  Note: As of right now mysql does not currently support this
	#private $sortorder;
	
	public function __construct($name, $length=null) {
		$this->setName($name);
		$this->setLength($length);
	}
	
	public function getName() { return $this->name; }
	public function getLength() { return $this->length; }
	#public function getDirection() { return $this->direction; }
	#public function getSortorder() { return $this->sortorder; }
	
	public function setName($name) { $this->name = $name; }
	public function setLength($length) { $this->length = $length; }
	#public function setDirection($direction) { $this->direction = $direction; }
	
}

?>