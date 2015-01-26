<?php

class ConfigValueControl extends WebControl {
	
	protected function init() {
		parent::init();
		
		$this->setInitParams(array(
			'wrapOutput' => false,
			'name' => ''
		));
		
	}
	public function prepareContent() {
		if (strlen($this->getParam('name')) > 0) {
			$value = ConfigurationManager::get($this->getParam('name'));
			$this->setRenderedContent( ConfigurationManager::get($this->getParam('name')) );
		}
	}
}

?>