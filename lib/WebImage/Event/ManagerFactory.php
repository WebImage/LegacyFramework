<?php

namespace WebImage\Event;

use WebImage\ServiceManager\IFactory;
use WebImage\ServiceManager\IServiceManager;

class ManagerFactory implements IFactory
{
	public function createService(IServiceManager $sm)
	{
		return new Manager();
	}
}