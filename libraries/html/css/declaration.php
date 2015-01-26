<?php
FrameworkManager::loadLibrary('html.css.irenderable');
class CWI_HTML_CSS_Declaration implements CWI_HTML_CSS_IRenderable {
	private $name;
	private $expression;
	function __construct($name, $expression) {
		$this->name = $name;
		$this->expression = $expression;
	}
	function render() {
		return $this->name . ':' . $this->expression->render();
	}
	
	function createFromNameAndExpression($name, $expression) {
		FrameworkManager::loadLibrary('html.css.expression');
		$declaration = new CWI_HTML_CSS_Declaration($name, new CWI_HTML_CSS_Expression($expression));
		return $declaration;
	}
}