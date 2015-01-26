<?php

class CWI_CLASS_SCANNER_ClassDef {
	/**
	 * @property string $name The name of the class
	 * @property string $type The type of class object - uses PHP type constants T_CLASS, T_EXTENDS, T_INTERFACE
	 * @property CWI_CLASS_SCANNER_ClassCollection $_extends The immediate classes that this class extends
	 * @property CWI_CLASS_SCANNER_ClassCollection $interfaces The immediate interfaces that this class implements
	 * @property string $file The file where the class definition is located
	 **/

	private $name, $type, $_extends, $interfaces, $file;
	function __construct() {
		$this->_extends = new CWI_CLASS_SCANNER_ClassCollection(); // Just in case we can some day extend multiple classes :)
		$this->interfaces = new CWI_CLASS_SCANNER_ClassCollection();
	}
	public function getName() { return $this->name; }
	public function getType() { return $this->type; }
	public function getFile() { return $this->file; }
	
	public function setName($name) { $this->name = $name; }
	public function setType($type) { $this->type = $type; }
	public function setFile($file) { $this->file = $file; }
	public function addExtends($extends) { $this->_extends->add($extends); }
	public function addInterface($interface) { $this->interfaces->add($interface); }
	
	public function getExtends() { return $this->_extends; }
	public function getInterfaces() { return $this->interfaces; }
	
	public function doesExtend($class) { return $this->_extends->hasValue($class); }
	public function doesImplement($interface) { return $this->interfaces->hasValue($interface); }
	
}

?>