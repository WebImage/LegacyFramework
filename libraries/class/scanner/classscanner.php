<?php

FrameworkManager::loadLibrary('class.scanner.classcollection');
FrameworkManager::loadLibrary('class.scanner.classdef');
FrameworkManager::loadLibrary('class.scanner.classdeftraversal');
FrameworkManager::loadLibrary('class.scanner.classscannermissingclassreference');

define('SCAN_CLASS_NAME', 1);
define('SCAN_CLASS_EXTENDS', 2);
define('SCAN_CLASS_IMPLEMENTS', 4);
/**
 * Scans a directory for classes by parsing the text of included files
 **/
class CWI_CLASS_SCANNER_ClassScanner {
	private $classes;
	private $fileExtensions = array('php');
	// Keep track of missing classes and interfaces
	private $missing;

	function __construct() {
		$this->classes = new Dictionary();
		$this->missing = new Dictionary();
	}
	
	/**
	 * Indicated that a class has been found in-case it was previously marked as missing
	 **/
	/*
	addMissing($str, $class_def->getName(), T_INTERFACE);
	*/
	protected function addMissing($missing_class, $requesting_class, $requesting_class_type, $file=null) {
		if (!$references = $this->missing->get($missing_class)) {
			$references = new Collection();
			$this->missing->set($missing_class, $references);
		}
		
		$missing = new CWI_CLASS_SCANNER_ClassScannerMissingClassReference($requesting_class, $requesting_class_type, $file);
		$references->add($missing);
	}
	/**
	 * @return Dictionary
	 **/
	public function getMissing() {
		return $this->missing;
	}
	public function scanFile($file) {
		if (!file_exists($file)) return false;
		$this->scanText( file_get_contents($file), $file);
	}
	public function scanDir($dir) {
		if (!file_exists($dir)) return false;
		
		$files = scandir($dir);

		foreach($files as $file) {
			
			if (!in_array($file, array('.', '..'))) {
				
				if (filetype($dir.$file) == 'dir') {
					
					$this->scanDir($dir.$file.'/');
					
				} else {
					
					$extension = substr($file, strrpos($file, '.')+1);
					
					if (in_array($extension, $this->fileExtensions)) {
						
						$this->scanFile($dir.$file);
						
					}
				}
			}
		}
	}
	
	/**
	 * Indicated that a class has been found in-case it was previously marked as missing
	 **/
	protected function found($class) {
		$this->missing->del($class);
	}
	
	protected function addClassDef($class_def) {
		$this->classes->set($class_def->getName(), new CWI_CLASS_SCANNER_ClassDefTraversal($class_def, $this));
	}
	
	private function scanText($text, $file=null) {
		$tokens = token_get_all($text);
		$in_class = false;
		$class_def = null;
		$scan_mode = null;
		$class_type = null;
		foreach($tokens as $token) {
			
			if (is_array($token)) {
				
				$token_key = $token[0];
				
				#echo 'Token Type: ' . token_name($token_key) . ' (' . T_CLASS . ' = ' . T_INTERFACE . ')<br />';
				if ($in_class) {
					
					if ($token_key == T_STRING) {
						
						$str = $token[1];
						
						switch ($scan_mode) {
							
							case SCAN_CLASS_NAME: // Create new CWI_CLASS_SCANNER_ClassDef
						
								$class_def = new CWI_CLASS_SCANNER_ClassDef();
								$class_def->setName($str);
								$class_def->setType($class_type);
								$class_def->setFile($file);
								
								$this->addClassDef($class_def);
								
								break;
							
							case SCAN_CLASS_EXTENDS:
								
								if (!$this->classExists($str)) $this->addMissing($str, $class_def->getName(), T_EXTENDS, $file);
								$class_def->addExtends($str);
								
								// There is a special case where one interface can extend another, in which case we also want to add the parent class as an interface (e.g. interface IChildInterface extends IParentInterface)
								if ($class_def->getType() == T_INTERFACE) {
									if (!$this->classExists($str)) $this->addMissing($str, $class_def->getName(), T_INTERFACE, $file);
									$class_def->addInterface($str);
								}
								break;
							
							case SCAN_CLASS_IMPLEMENTS:
							
								if (!$this->classExists($str)) $this->addMissing($str, $class_def->getName(), T_INTERFACE, $file);
								$class_def->addInterface($str);
								break;
						}
						
					} else if ($token_key == T_EXTENDS) {
						
						$scan_mode = SCAN_CLASS_EXTENDS;
						
					} else if ($token_key == T_IMPLEMENTS) {
						
						$scan_mode = SCAN_CLASS_IMPLEMENTS;
						
					}
				}
				
				if (in_array($token_key, array(T_CLASS,T_INTERFACE,T_ABSTRACT))) {
					$in_class = true;
					$scan_mode = SCAN_CLASS_NAME;
					$class_type = $token_key;		
					
				}
			} else { // Single character
				
				if ($in_class) {
					
					if ($token == '{') {
						// Reset values
						$in_class = false;
						$class_type = null;
					}
					
				}
			}
		}
	}
	
	public function getClass($name) {
		return $this->classes->get($name);
	}
	public function getClasses() {
		return $this->classes;
	}
	public function getClassDefs() {
		$defs = new Collection();
		$classes = $this->getClasses();
		while ($class = $classes->getNext()) {
			$def = $class->getDef();
			$defs->add($def);
		}
		return $defs;
	}
	
	public function classExists($name) {
		return $this->classes->isDefined($name);
	}
	/**
	 * Methods dealing with retrieving object hierarchy
	 **/
	private function buildParentRelationship() {
		$class_fields = $this->classes->getAll();
		
		$parent_relationship = array();
		
		while ($class_field = $class_fields->getNext()) {
			
			$class_def = $class_field->getDefinition()->getDef();
			
			$extends = $class_def->getExtends();
			
			$parents = array();
			if ($extends->getCount() > 0) {
				$parents = $extends->getAll();
			} else {
				$parents = array('__NONE__');
			}
			
			foreach($parents as $parent) { // There should generally only be one parent, unless PHP allows multiple extends in the future
				$parent_relationship[$parent] = $class_def->getName();
			}
			
		}
		
		return $parent_relationship;	
	}
	
	private function buildHierarchy($class='__NONE__', array $parent_relationships) {
		
		$classes = new Collection();
		
		
	}
	
	public function getHierarchy() {
		$parent_relationships = $this->buildParentRelationship();
		$this->buildHierarchy($parent_relationships);
	}
	
	/**
	 * Get classes that implement a particular interface
	 **/
	public function getClassesThatImplement($interface) {
		$return = array();
		$class_fields = $this->getClasses()->getAll();
				
		while ($class_field = $class_fields->getNext()) {
			
			$class = $class_field->getDefinition();
			
			$name = $class->getDef()->getName();
			
			if ($class->doesImplement($interface)) {
				
				array_push($return, $name);
				
			}
			
		}
		return $return;
	}
}

?>