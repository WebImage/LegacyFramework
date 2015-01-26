<?php

FrameworkManager::loadLibrary('plugins.plugin');
FrameworkManager::loadLogic('remoterequest');
class CWI_PLUGIN_ResourceFilePlugin extends CWI_PLUGIN_Plugin {
	private $baseDir, $localPath;
	private $download;
	public function __construct($name, $base_dir, $local_path, $download, $version=1) {
		parent::__construct($name, $version);
		$this->baseDir = $base_dir;
		$this->localPath = $local_path;
		$this->download = $download;
	}
	public function getBaseDir() { return $this->baseDir; }
	public function getLocalPath() { return $this->localPath; }
	public function getSystemPath() { return $this->getBaseDir() . $this->getLocalPath(); }
	public function getDownloadLocation() { return $this->download; }
	public function isInstalled() { return (file_exists($this->getSystemPath())); }
	
	protected function executeInstallation() {
		$download_location = $this->getDownloadLocation();
		$system_path = $this->getSystemPath();
		
		if (empty($download_location)) {
			$this->setInstallationStatus('Download location not defined for: ' . $this->getName());
			return -1;
		}
		if (empty($system_path)) {
			$this->setInstallationStatus('System path not defined for: ' . $this->getName());
			return -1;
		}
		ConfigurationManager::set('REMOTEREQUEST_IGNORESSLERRORS', 'true');
		if ($response = RemoteRequestLogic::getXmlResponse($download_location)) {
			if ($response->getParam('status') == 'success') {
				if ($file_contents = $response->getData('file/fileContents')) {
					$directory = dirname($system_path) . '/';
					if (!file_exists($directory)) {
						if (!@mkdir($directory, 0777, true)) {
							$this->setInstallationStatus('Unable to create directory ' . $directory . '.');
							return -1;
						}
					}
					if (!is_writable($directory)) {
						$this->setInstallationStatus('Directory is not writable: ' . $directory);
						return -1;
					}
					
					if (!@file_put_contents($system_path, $file_contents)) {
						$this->setInstallationStatus('Unable to write file at: ' . $system_path . '.');
						return -1;
					}
					return 1;
				} else {
					$this->setInstallationStatus('Unable to determine file contents for file downloaded from ' . $download_location . '.');
					return -1;
				}
			} else {
				$this->setInstallationStatus('Remote download to ' . $download_location . ' failed.');
				return -1;
			}
		} else {
			$this->setInstallationStatus('Unable to initiate remote request to download location: ' . $download_location . '.');
			return -1;
		}
	}
}

?>