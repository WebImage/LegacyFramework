<?php

class ProviderManager {
	var $m_defaultProvider;
	var $m_provider;
	var $m_providers = array(); // Array of ProviderCollection

	function getInstance() { return Singleton::getInstance('ProviderManager'); }
	
	function addProvider($name, $instantiated_class) {}
	function getProvider($provider_name=null) {}
	function getProviders() { return $this->m_providers; }
	function isStarted() {
	}
}
