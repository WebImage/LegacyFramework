<?php

class PageHeaderControl extends WebControl {
	
	protected function init() {
		parent::init();
		$this->setInitParam('wrapOutput', false);
	}
	/**
	 * Override contentFinalized.  All controls have been had their regular content rendered, so now we can output any headers that need to be added
	 **/
	public function contentFinalized() {
		$debug = ($this->isDebugMode()) ? 'true':'false';
		/*
		$output = '<?php echo Page::renderHeader(' . $debug . '); ?>';
		*/
		$output = Page::renderHeader(' . $debug . ');
		if ($this->isDebugMode()) {
			$output = '<!-- // Begin Header // -->' . "\r\n" . $output . "\r\n" . '<!-- // End Header // -->' . "\r\n";
		}
		$this->setRenderedContent($output);
	}
	
	private function isDebugMode() { return ($this->getParam('debug') == 'true' || $this->getParam('debug') === true); }
}

?>