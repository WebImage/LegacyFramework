<?php

namespace WebImage\ServiceManager;

use WebImage\ServiceManager\IServiceManager;

interface IServiceManagerConfig {
	public function configureServiceManager(LegacyServiceManager $serviceManager);
}
