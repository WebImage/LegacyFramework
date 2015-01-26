<?php

class ApiRequest {
	/**
	 * @property string $method A string representation of a method, e.g. Page.getPages
	 **/
	private $method;
	/**
	 * @property Dictionary $parameters Any parameters that will be passed to the method
	 **/
	private $parameters;
	/**
	 * @property MembershipUser $user A membership user
	 **/
	private $user;
	
	function __construct($method, $parameters, $user=null) {
		$this->method = $method;
		$this->parameters = $parameters;
		$this->user = $user;
	}
	
	public function getUser() { return $this->user; }
	public function getMethod() { return $this->method; }
	public function getParameters() { return $this->parameters; }

}


?>