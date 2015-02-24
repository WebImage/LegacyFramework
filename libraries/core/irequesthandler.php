<?php

interface IRequestHandler extends IServiceManagerAware {

	/**
	 * Check whether this request handler can handle the path requested
	 * @param string $path
	 * @return bool
	 */
	public function canHandleRequest($path=null);

	/**
	 * Render the contents of the request
	 * @return string
	 */
	public function render();

	/**
	 * @return PageRequest
	 */
	public function getPageRequest();

	/**
	 * @return int
	 */
	public function getPageId();

	/**
	 * @param PageRequest $page_request
	 * @return mixed
	 */
	public function setPageRequest($page_request);

	/**
	 * Whether stats should be run for this request
	 * @return bool
	 */
	public function statsEnabled();

	/**
	 * Whether or not this request should be considered an admin request
	 * @return bool
	 */
	public function isAdminRequest();
	/**
	 * @return ServiceManager
	 */
	public function getServiceManager();
	/**
	 * @param ServiceManager $service_manager
	 * @return IRequestHandler
	 */
	public function setServiceManager(IServiceManager $service_manager);
}
