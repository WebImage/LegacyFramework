<?php

/**
 * Allows Stylesheet (CSS) files (and later text) to be added to a file without having to use PHP in template files
 */
/**
 * 04/26/2010	(Robert Jones) Created file
 */
class StylesheetControl extends WebControl {
	
	var $m_file;
	var $m_media = 'all';
	var $m_type = 'text/css';
	var $m_rel = 'stylesheet';
	var $m_addToTop = false;
	
	protected function init() {
		parent::init();
		$this->setInitParam('wrapOutput', false);
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
	private function getFile() { return $this->m_file; }
	private function getMedia() { return $this->m_media; }
	private function getType() { return $this->m_type; }
	private function getRel() { return $this->m_rel; }
	private function shouldAddToTop() { $this->m_addToTop; }
	
	public function getTranslatedFile() {
		$file = ConfigurationManager::getValueFromString($this->getFile());
		if (substr($file, 0, 2) == '~/') {
			$file = ConfigurationManager::get('DIR_WS_ASSETS_CSS') . substr($file, 2);
		}
		return $file;
	}
}

?>