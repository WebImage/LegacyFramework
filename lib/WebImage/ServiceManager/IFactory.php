<?php

namespace WebImage\ServiceManager;

use WebImage\ServiceManager\IServiceManager;

interface IFactory {
	public function createService(IServiceManager $sm);
}