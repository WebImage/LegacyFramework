<?php

namespace WebImage\ExperienceProfile;

use WebImage\ServiceManager\IFactory;
use WebImage\ServiceManager\IServiceManager;
use WebImage\Provider\Config as ProviderConfig;

class ProfileManagerFactory implements IFactory {

	protected $configKey = 'profile';
	protected $configKeyProviders = 'providers';
	protected $configKeyDefaultProvider = 'defaultProvider';
	protected $configKeyProviderName = 'name';
	protected $configKeyProviderClassName = 'className';
	protected $configKeyProviderClassFile = 'classFile';

	public function createService(IServiceManager $sm) {
		$config = $sm->get('ApplicationConfig');
		$config = $config[$this->configKey];

		$providers = $config[$this->configKeyProviders];
		$default_provider = $config[$this->configKeyDefaultProvider];

		$manager = new ProfileManager();
		$manager->setDefaultProvider($default_provider);

		/** @var \WebImage\Core\Dictionary $p_config */
		foreach($providers as $p_name => $p_config) {

			// Retrieve values required to create profile class
			$name = $p_config->get($this->configKeyProviderName); // Allows name to be overridden with "name" config key
			if (empty($name)) $name = $p_name;

			$class_name = $p_config->get($this->configKeyProviderClassName);
			$class_file = $p_config->get($this->configKeyProviderClassFile);

			// Remove provider config values that are no longer necessary
			$p_config->del($this->configKeyProviderName);
			$p_config->del($this->configKeyProviderClassName);
			$p_config->del($this->configKeyProviderClassFile);

			// Rewrite generic config as ProfileConfig
			$p_config = new ProviderConfig($name, $class_name, $class_file, $p_config);
			$manager->addProviderConfig($p_config);
		}
		return $manager;
	}
}