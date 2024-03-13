<?php

namespace WebImage\Application;

use WebImage\Config\LegacyConfig;
use WebImage\ServiceManager\IServiceManagerAware;
use WebImage\ServiceManager\LegacyServiceManager;
use WebImage\ServiceManager\IServiceManager;
use WebImage\ServiceManager\LegacyServiceManagerConfig;

class LegacyApplication implements IServiceManagerAware {

	private $serviceManager;

	/**
	 * @param LegacyConfig $config
	 */
	function __construct(LegacyConfig $config, LegacyServiceManager $service_manager) {
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
	 * @param LegacyConfig $config
	 * @return LegacyApplication
	 */
	public static function create(LegacyConfig $config) {

		$service_manager_config = isset($config['serviceManager']) ? $config['serviceManager'] : array();
		$service_manager = new LegacyServiceManager( new LegacyServiceManagerConfig($service_manager_config) );
		$service_manager->setService('ApplicationConfig', $config);

		$application = new LegacyApplication($config, $service_manager);
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
