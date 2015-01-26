<?php

class RequestHandler {
	private $pageRequest;
	function __construct($page_request=null) {
		$this->setPageRequest($page_request);
		return true;
	}
	public function canHandleRequest($path) {
		return false;
	}
	public function render() {
		return 'RequestHandler<br />';
	}
	#public function getRequestedPath() { return $this->requestPath; }
	#public function setRequestedPath($request_path) { $this->requestPath = $request_path; }
	public function getPageRequest() { return $this->pageRequest; }
	public function setPageRequest($page_request) { $this->pageRequest = $page_request; }
	public function isAdminRequest() { return false; }
	public function getPageId() { return false; }
	public function statsEnabled() { return true; }
}
