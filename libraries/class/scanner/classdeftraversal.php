<?php

class CWI_CLASS_SCANNER_ClassDefTraversal {
	private $def, $scanner;
	function __construct(CWI_CLASS_SCANNER_ClassDef $def, CWI_CLASS_SCANNER_ClassScanner $scanner) {
		$this->def = $def;
		$this->scanner = $scanner;
	}
	public function getDef() { return $this->def; }
	
	public function getExtends() {
		$parents = $this->getParents(T_EXTENDS);
		return $parents['results'];		
	}
	
	public function getInterfaces() {
		$parents = $this->getParents(T_INTERFACE);
		return $parents['results'];
	}
	
	private function getParents($type=null) {

		$return = array(
			'results' => array(),
			'missing' => array(
				'interfaces' => array(),
				'classes' => array(),
			)
		);
		
		$def = $this->getDef();
		$interfaces = $def->getInterfaces();
		$extends = $def->getExtends();
		/**
		 * Get all interfaces and their interfaces
		 **/
		while ($interface = $interfaces->getNext()) {
			
			if (is_null($type) || $type == T_INTERFACE) array_push($return['results'], $interface);
			
			if ($class = $this->scanner->getClass($interface)) {
				
				// Get all interfaces for this class
				$return['results'] = array_merge($return['results'], $class->getInterfaces());
				
			}
			
		}
		
		/**
		 * Get all classes that this class inherits from
		 **/
		while ($extend_class = $extends->getNext()) {
			
			if (is_null($type) || $type == T_EXTENDS) array_push($return['results'], $extend_class);
			
			if ($class = $this->scanner->getClass($extend_class)) {
				
				// Get all interfaces for this class 
				if (is_null($type) || $type == T_INTERFACE) $return['results'] = array_merge($return['results'], $class->getInterfaces());
				// Get all extends for this class
				if (is_null($type) || $type == T_EXTENDS) $return['results'] = array_merge($return['results'], $class->getExtends());
				
			}
		}
		
		return $return;
		
	}
	
	public function doesExtend($class) {
		$extends = $this->getExtends();
		return in_array($class, $extends);
	}
	
	public function doesImplement($interface) {
		
		$interfaces = $this->getInterfaces();
		return in_array($interface, $interfaces);
		
	}
	
}

?>