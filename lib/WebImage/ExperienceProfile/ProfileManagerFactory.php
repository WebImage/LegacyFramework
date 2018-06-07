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
	protected $configKeyDomainMapping = 'domainMapping';
	protected $configKeyProviderPriority = 'priority';

	public function createService(IServiceManager $sm) {
		$config = $sm->get('ApplicationConfig');
		$config = $config[$this->configKey];

		$providers = $config[$this->configKeyProviders];
		$default_provider = $config[$this->configKeyDefaultProvider];
		$default_provider_class = $providers[$default_provider]->get($this->configKeyProviderClassName);
		
		$manager = new ProfileManager();
		$manager->setDefaultProviderName($default_provider);

		$domain_mapping = $config[$this->configKeyDomainMapping];

		if ($domain_mapping) {
			foreach($domain_mapping as $profile_name => $domains) {
				foreach($domains as $domain) {
					$manager->setDomainProfile($domain, $profile_name);
				}
			}
		}

		$configs = array();
		/** @var \WebImage\Core\Dictionary $p_config */
		foreach($providers as $p_name => $p_config) {

			// Retrieve values required to create profile class
			$name = $p_config->get($this->configKeyProviderName); // Allows name to be overridden with "name" config key
			if (empty($name)) $name = $p_name;

			$class_name = $p_config->get($this->configKeyProviderClassName);
			if (empty($class_name)) {
				if ($p_name == $default_provider) {
					throw new \RuntimeException(sprintf('%s profile is missing a class name', $p_name));
				}
				$class_name = $default_provider_class;
			}
			
			$class_file = $p_config->get($this->configKeyProviderClassFile);

			// Remove provider config values that are no longer necessary
			$p_config->del($this->configKeyProviderName);
			$p_config->del($this->configKeyProviderClassName);
			$p_config->del($this->configKeyProviderClassFile);
			
			// Rewrite generic config as ProfileConfig
			$p_config = new ProviderConfig($name, $class_name, $class_file, $p_config);
			$configs[] = $p_config;

		}

		// Sort providers by priority
		usort($configs, function(ProviderConfig $a, ProviderConfig $b) {
			$a = $a->getMetaValue($this->configKeyProviderPriority, 0);
			$b = $b->getMetaValue($this->configKeyProviderPriority, 0);
			if ($a < $b) return 1;
			else if ($a > $b) return -1;
			else return 0;
		});
		
		foreach($configs as $config) {
			$manager->addProviderConfig($config);
		}

		return $manager;
	}

}