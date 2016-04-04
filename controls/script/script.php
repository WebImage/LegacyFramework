<?php

/**
 * Allows Stylesheet (CSS) files (and later text) to be added to a file without having to use PHP in template files
 */
/**
 * 04/26/2010	(Robert Jones) Created file
 */
class ScriptControl extends WebControl {
	
	protected function init() {
		parent::init();
		$this->setInitParam('wrapOutput', false);
		$this->setInitParam('file', '');
		$this->setInitParam('type', 'text/javascript');
		$this->setInitParam('addToTop', false);
	}
	public function prepareContent() {
		Page::addScript(
				$this->getTranslatedFile(), 
				$this->getType(), 
				$this->shouldAddToTop()
				);
	}
	private function getFile() { return $this->getParam('file'); }
	private function getType() { return $this->getParam('type'); }
	private function shouldAddToTop() { $this->getBoolParam('addToTop'); }
	
	public function getTranslatedFile() {
		$file = ConfigurationManager::getValueFromString($this->getFile());
		if (substr($file, 0, 2) == '~/') {
			$file = ConfigurationManager::get('DIR_WS_ASSETS_JS') . substr($file, 2);
		}
		return $file;
	}
}

?>