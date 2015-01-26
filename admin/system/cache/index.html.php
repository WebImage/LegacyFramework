<?php
/**
 * Still need to add logic for clearing a specific cache directory
 **/
 

$dir = ConfigurationManager::get('DIR_FS_CACHE');

if (file_exists($dir)) {
	
	if (is_writable($dir)) {
	
		$dh = opendir($dir);
		
		$caches = new Collection();
		
		while ($file = readdir($dh)) {

			if (filetype($dir . $file) == 'dir' && !in_array($file, array('.','..'))) {
				
				$cache = new stdClass();
				$cache->cache = $file;
				$caches->add($cache);
				
			}
			
		}
		
		closedir($dh);
	
		function sort_cache($a, $b) {
			return strcmp($a->cache, $b->cache);
		}
		
		usort($caches->getAll(), 'sort_cache');
				
		if ($dg_cache = Page::getControlById('dg_cache')) {

			$dg_cache->setData($caches);
			
		}
		
		if ($cache = Page::get('clearcache')) {
		}
	
	} else {
		
		ErrorManager::addError('Cache directory does not writable ' . $dir);
		
	}
	
} else {
	
	ErrorManager::addError('Cache directory does not exist ' . $dir);
	
}

?>