<?php
FrameworkManager::loadLibrary('html.css.irenderable');
class CWI_HTML_CSS_Selector implements CWI_HTML_CSS_IRenderable {
	private $selectors = array();
	function __construct() {
	}
	function addSimpleSelector($css_simple_selector) {
		array_push($this->selectors, $css_simple_selector);
	}
	function render() {
		$selectors = array();
		foreach($this->selectors as $selector) {
			array_push($selectors, $selector->render());
		}
		return implode(',', $selectors);
	}
	function createFromSelectorText($text) {
		FrameworkManager::loadLibrary('html.css.simpleselector');
		$selector = new CWI_HTML_CSS_Selector();
		$simple_selector = new CWI_HTML_CSS_SimpleSelector($text);
		$selector->addSimpleSelector($simple_selector);
		return $selector;
		
	}
}

?>