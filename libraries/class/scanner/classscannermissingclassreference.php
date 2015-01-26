<?php

class CWI_CLASS_SCANNER_ClassScannerMissingClassReference {
	private $class, $type, $file;
	function __construct($class, $type, $file=null) {
		$this->class = $class;
		$this->type = $type;
		$this->file = $file;
	}
	public function getClass() { return $this->class; }
	public function getType() { return $this->type; }
	public function getFile() { return $this->file; }
	
}

?>