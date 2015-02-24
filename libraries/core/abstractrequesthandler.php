<?php

FrameworkManager::loadLibrary('core.irequesthandler');

abstract class AbstractRequestHandler implements IRequestHandler {
	/**
	 * @var PageRequest
	 */
	private $pageRequest;
	/**
	 * @var IServiceManager $serviceManager;
	 */
	private $serviceManager;
	function __construct($page_request=null) {
		$this->setPageRequest($page_request);
		return true;
	}
	public function canHandleRequest($path=null) {
		return false;
	}
	public function render() {
		return 'RequestHandler';
	}
	#public function getRequestedPath() { return $this->requestPath; }
	#public function setRequestedPath($request_path) { $this->requestPath = $request_path; }
	public function getPageRequest() { return $this->pageRequest; }
	public function setPageRequest($page_request) { $this->pageRequest = $page_request; }
	public function isAdminRequest() { return false; }
	public function getPageId() { return false; }
	public function statsEnabled() { return true; }

	public function getServiceManager() { return $this->serviceManager; }
	public function setServiceManager(IServiceManager $service_manager) {
		$this->serviceManager = $service_manager;
		return $this;
	}
}
