<?php

class CWI_ASSETS_AssetFileType {
	private $name, $classFile, $className; // string
	private $extensions = array();
	private $adminPath;
	public function __construct($name, $class_file, $class_name, $admin_path) {
		$this->setName($name);
		$this->setClassFile($class_file);
		$this->setClassName($class_name);
		$this->setAdminPath($admin_path);
	}
	public function getName() { return $this->name; }
	public function getClassFile() { return $this->classFile; }
	public function getClassName() { return $this->className; }
	public function getAdminPath() { return $this->adminPath; }
	
	public function setName($name) { $this->name = $name; }
	public function setClassFile($file) { $this->classFile = $file; }
	public function setClassName($name) { $this->className = $name; }
	public function setAdminPath($admin_path) { $this->adminPath = $admin_path; }
	
	public function getExtensions() { return $this->extensions; }
	public function addExtension($extension) { array_push($this->extensions, $extension); }
}

?>