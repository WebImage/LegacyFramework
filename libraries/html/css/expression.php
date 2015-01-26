<?php
FrameworkManager::loadLibrary('html.css.irenderable');
class CWI_HTML_CSS_Expression implements CWI_HTML_CSS_IRenderable {
	private $expression;
	function __construct($expression) {
		$this->expression = $expression;
	}
	function render() {
		return $this->expression;
	}
}