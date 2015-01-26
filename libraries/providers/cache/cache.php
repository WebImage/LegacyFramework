<?php

/**
 * Base CacheProvider implementation
 */
class CWI_PROVIDER_CacheProvider extends ProviderBase {
	protected function getCacheDir() { return ConfigurationManager::get('DIR_FS_CACHE') . $this->getName() . '/'; }
	protected function getCacheFileNameByKey($key) { return $key . '.cache'; }
	
	public function saveCacheByKey($key, $file_contents) {
		if (CWI_MANAGER_CacheManager::isCacheEnabled()) {
			$dir = $this->getCacheDir();
			$file = $this->getCacheFileNameByKey($key);
			if (strlen($dir) && !file_exists($dir)) {
				if (!@mkdir($dir, 0777, true)) return false;
			}
			$path = $dir . $file;
			if (@file_put_contents($path, $file_contents)) return true;
		}
		return false;
	}
	/**
	 * @param string $key
	 * @param int $timeout Number of seconds
	 */
	public function getCacheByKey($key, $timeout=null) {
		if (CWI_MANAGER_CacheManager::isCacheEnabled()) {

			$path = $this->getCacheDir() . $this->getCacheFileNameByKey($key);

			if (file_exists($path)) {
				
				// Check if file has timedout
				if (is_numeric($timeout)) {
					$last_modified = filemtime($path);
					$cache_age = time() - $last_modified;
					if ($cache_age > $timeout) return false;
				}
				
				if ($file_contents = @file_get_contents($path)) {

					return $file_contents;
				}
			}
		}
		return false;
	}
	/**
	 * Returns the age of a cached key in seconds
	 */
	public function getCacheAgeByKey($key) {
		if (CWI_MANAGER_CacheManager::isCacheEnabled()) {

			$path = $this->getCacheDir() . $this->getCacheFileNameByKey($key);

			if (file_exists($path)) {
				
				// Get age of cache
				$last_modified = filemtime($path);
				$cache_age = time() - $last_modified;
				return $cache_age;
				
			}
		}
		return false;
	}
	
	public function deleteCacheByKey($key) {
		$dir = $this->getCacheDir();
		$file = $this->getCacheFileNameByKey($key);
		if (file_exists($dir . $file)) {
			unlink($dir.$file);
		}
	}
}

?>