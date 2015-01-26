<?php

class LazyProviderDictionary extends ProviderDictionary {
	/**
	 * @param string $key
	 * @param LazyProvider $lazy_provider (not strongly typed because it overrides parent set()
	 */ 
	public function set($key, $lazy_provider) { 
		if (!is_a($lazy_provider, 'LazyProvider')) throw new Exception(sprintf('%s was expecting lazy provider of type LazyProvider', __METHOD__));
		parent::set($key, $lazy_provider);
	}
}
