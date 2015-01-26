<?php

class ProfileProvider extends ProviderBase {
	private $name;
	function getName() { return $this->name; }
	function setName($name) { $this->name = $name; }
	function createFromPageRequest($page_request) { return false; }
}