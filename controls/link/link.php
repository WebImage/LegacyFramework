<?php

FrameworkManager::loadControl('html');

class LinkControl extends WebControl {
	const PARAM_HREF = 'href';
	protected function init() {

		parent::init();

		$this->addPassThru(self::PARAM_HREF);

		$this->setInitParam('wrapOutput', '<a%s>%s</a>');
	}

	public function getHrefRaw() {
		return $this->getParam(self::PARAM_HREF);
	}

	/**
	 * Escape any required values from the HREF string
	 * @return string
	 */
	public function getHref() {

		// Swap out any configuration reference, e.g. %DIR_WS_HOME%, with configured values
		$href = ConfigurationManager::getValueFromString($this->getHrefRaw());

		// Replace any system relative links, e.g. ~/page => DIR_WS_HOME or DIR_WS_ADMIN_CONTENT if in admin mode
		if (substr($href, 0, 2) == '~/') {
			if (Page::isAdminRequest()) {
				$href = ConfigurationManager::get('DIR_WS_ADMIN_CONTENT') . substr($href, 2);
			} else {
				$href = ConfigurationManager::get('DIR_WS_HOME') . substr($href, 2);
			}
		}

		return $href;
	}
	public function setHref($href) { $this->setParam(self::PARAM_HREF, $href); }

	protected function getValueForParamString($name) {
		$value = parent::getValueForParamString($name);

		if ($value && $name == self::PARAM_HREF) {
			$value = $this->getHref();
		}
		return $value;
	}
}
