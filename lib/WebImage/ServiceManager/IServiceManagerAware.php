<?php

namespace WebImage\ServiceManager;

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
