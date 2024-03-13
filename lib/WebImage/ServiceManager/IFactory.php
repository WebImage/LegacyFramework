<?php

namespace WebImage\ServiceManager;

interface IFactory {
	public function createService(IServiceManager $sm);
}
