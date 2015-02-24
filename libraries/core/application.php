<?php

use Zend\Json\Server\Smd\Service;

class Application implements IServiceManagerAware {

	private $serviceManager;

	/**
	 * @param array $config
	 */
	function __construct(array $config, ServiceManager $service_manager) {
		$this->config = $config;
		$this->serviceManager = $service_manager;
	}


	public function getServiceManager() {
		return $this->serviceManager;
	}
	public function setServiceManager(IServiceManager $sm) {
		$this->serviceManager = $sm;
	}

	public static function create(array $config = array()) {

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