<?php

FrameworkManager::loadControl('html');

class LinkControl extends WebControl {
	
	protected function init() {
		parent::init();

		$href = $this->getParam('href');
		if (substr($href, 0, 2) == '~/') {
			if (Page::isAdminRequest()) {
				$href = ConfigurationManager::get('DIR_WS_ADMIN_CONTENT') . substr($href, 2);
			} else {
				$href = ConfigurationManager::get('DIR_WS_HOME') . substr($href, 2);
			}
		}
		$this->setInitParams(array(
			'wrapOutput' => '<a%s>%s</a>',
			'href' => $href
		));
		#$this->setWrapOutput('<a href="' . str_replace('%', '%%', $this->getHref()) . '"%s>%s</a>');
	}

	function getHrefRaw() {
		return $this->getParam('href');
	}
	
	function getHref() {
		return ConfigurationManager::getValueFromString($this->getHrefRaw());
	}
	function setHref($href) { $this->setParam('href', $href); }
}

?>