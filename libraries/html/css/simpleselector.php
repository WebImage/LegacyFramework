<?php
FrameworkManager::loadLibrary('html.css.irenderable');
class CWI_HTML_CSS_SimpleSelector implements CWI_HTML_CSS_IRenderable {
	private $name;
	function __construct($name) {
		$this->name = $name;
	}
	function render() {
		return $this->name;
	}
}