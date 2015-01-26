<?php

interface CWI_API_IHandler {
	function __construct(CWI_API_Request $request);
	
	/**
	 * @return CWI_API_Request
	 **/
	public function getRequest();
	/**
	 * Whether the handler can handle the requested method
	 * @return bool
	 **/
	public function canHandleRequest();
	/**
	 * Check user privileges to make sure that the user can access the method
	 * @return bool
	 **/
	public function canExecuteRequest();
	/**
	 * 
	 * @return string Any required format, e.g. XML, Json, Html, etc.
	 **/
	public function execute();
	/**
	 * @return void
	 **/
	public function addError($error_message);
	/**
	 * @return array
	 **/
	public function getErrors();
	/**
	 * @return bool
	 **/ 
	public function anyErrors();
	
}

abstract class CWI_API_Handler implements CWI_API_IHandler {
	private $request;
	private $errors; 
	
	function __construct($api_request) {
		$this->initMethods();
	}
	
	public function getRequest() { return $request; }
	
	public function canHandleMethod() { return true; }
	
	public function canHandleRequest() { 
		return $this->canHandleMethod();
	}

	public function canUserExecuteMethod() { return true; }
	
	public function canExecuteMethod() {
		return $this->canUserExecuteMethod();
	}
	
	public function addError($error_message) { array_push($this->errors, $error); }
	public function getErrors() { return $this->errors; }
	
	public function anyErrors() { return (count($this->errors) > 0); }
	
	function execute() {
#		if ($this->
	}
}


?>