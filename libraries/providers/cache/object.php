<?php

FrameworkManager::loadLibrary('providers.cache.cache');

/**
 * Same as CWI_PROVIDER_CacheProvider except that it first serializes the object before saving it as a string to a file
 */
class CWI_PROVIDER_ObjectCacheProvider extends CWI_PROVIDER_CacheProvider {
	public function saveCacheByKey($key, $object) {
		if (!is_object($object)) return false;
		return parent::saveCacheByKey($key, serialize($object));
	}
	public function getCacheByKey($key, $timeout=null) {

		if ($cache = parent::getCacheByKey($key, $timeout)) {
			
			if (isset($cache[0])) {
				try {
					$object = unserialize($cache);
				} catch (Exception $e) {
					return false;
				}

				if (is_object($object)) return $object;
			}
		}
		return false;
	}
}

?>