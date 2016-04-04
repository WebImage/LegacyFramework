<?php

/**
 * Allows Stylesheet (CSS) files (and later text) to be added to a file without having to use PHP in template files
 */
/**
 * 04/26/2010	(Robert Jones) Created file
 */
class StylesheetControl extends WebControl {
	
	protected function init() {
		parent::init();
		$this->setInitParam('wrapOutput', false);
		$this->setInitParam('media', 'all');
		$this->setInitParam('type', 'text/css');
		$this->setInitParam('rel', 'stylesheet');
		$this->setInitParam('addToTop', false);
	}
	public function prepareContent() {
		Page::addStyleSheet(
				    $this->getTranslatedFile(), 
				    $this->getMedia(), 
				    $this->getType(), 
				    $this->getRel(), 
				    $this->shouldAddToTop()
				    );
	}
	private function getFile() { return $this->getParam('file'); }
	private function getMedia() { return $this->getParam('media'); }
	private function getType() { return $this->getParam('type'); }
	private function getRel() { return $this->getParam('rel'); }
	private function shouldAddToTop() { $this->getBoolParam('addToTop'); }
	
	public function getTranslatedFile() {
		$file = ConfigurationManager::getValueFromString($this->getFile());
		if (substr($file, 0, 2) == '~/') {
			$file = ConfigurationManager::get('DIR_WS_ASSETS_CSS') . substr($file, 2);
		}
		return $file;
	}
}

?>