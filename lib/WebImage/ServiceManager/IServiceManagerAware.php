<?php

namespace WebImage\ServiceManager;

use WebImage\ServiceManager\IServiceManager;

interface IServiceManagerAware {
	/**
	 * @return IServiceManager
	 */
	public function getServiceManager();

	/**
	 * @param IServiceManager $sm
	 */
	public function setServiceManager(IServiceManager $sm);
}