<?php
FrameworkManager::loadLibrary('html.css.irenderable');
class CWI_HTML_CSS_RuleSet implements CWI_HTML_CSS_IRenderable {
	private $selector, $declarations=array();
	function __construct($selector, $declarations) {
		$this->selector = $selector;
		$this->declarations = $declarations;
	}
	function render() {
		$output = $this->selector->render() . '{';
		$declarations = array();
		foreach($this->declarations as $declaration) {
			array_push($declarations, $declaration->render());
		}
		$output .= implode(';', $declarations);
		$output .= '}';
		return $output;
	}
}