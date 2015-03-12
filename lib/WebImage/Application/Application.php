<?php

namespace WebImage\Application;

use WebImage\Config\Config;
use WebImage\ServiceManager\IServiceManagerAware;
use WebImage\ServiceManager\ServiceManager;
use WebImage\ServiceManager\IServiceManager;
use WebImage\ServiceManager\ServiceManagerConfig;

class Application implements IServiceManagerAware {

	private $serviceManager;

	/**
	 * @param Config $config
	 */
	function __construct(Config $config, ServiceManager $service_manager) {
		$this->config = $config;
		$this->serviceManager = $service_manager;
	}


	public function getServiceManager() {
		return $this->serviceManager;
	}
	public function setServiceManager(IServiceManager $sm) {
		$this->serviceManager = $sm;
	}

	/**
	 * @param Config $config
	 * @return Application
	 */
	public static function create(Config $config) {

		$service_manager_config = isset($config['serviceManager']) ? $config['serviceManager'] : array();
		$service_manager = new ServiceManager( new ServiceManagerConfig($service_manager_config) );
		$service_manager->setService('ApplicationConfig', $config);

		$application = new Application($config, $service_manager);
		#$service_manager->set('Application', $application);
		#$serviceManager->get('PluginManager')->loadPlugins();

		#$listenersFromAppConfig = isset($configuration['listeners']) ? $configuration['listeners'] : array();
		#$conf = $service_manager->get('Config');
		#$listenersFromConfigService = isset($conf['listeners']) ? $conf['listeners'] : array();
		#$listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));

		#return $service_manager->get('Application');
		return $application;

	}
}