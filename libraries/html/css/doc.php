<?php
/**
 * Referenced: http://www.codeproject.com/KB/recipes/CSSParser.aspx
 */
FrameworkManager::loadLibrary('html.css.irenderable');
/**
 * CWI_HTML_CSS_Doc
 *	CWI_HTML_CSS_RuleSet
 *		CWI_HTML_CSS_Selector
 *			CWI_HTML_CSS_SimpleSelector
 *		CWI_HTML_CSS_Declaration
 *			CWI_HTML_CSS_Expression
 *				CWI_HTML_CSS_Term <-- Not yet impelemented
 *					CWI_HTML_CSS_Function <!-- Not yet implemented
 *	CWI_HTML_CSS_Directive (type, name, express, mediums, directives, rulesets, declarations) <-- Not implemented
 */
class CWI_HTML_CSS_Doc implements CWI_HTML_CSS_IRenderable {
	private $ruleSets = array();
	//private $directives; // Not yet implemented
	
	private function removeUnimportantCode($css_text) {
		$css_text = preg_replace('#/\*.*\*/#ms', '', trim($css_text));
		return $css_text;
	}
	private function parseDeclarations($declaration_text) {
		$attributes = explode(';', $declaration_text);
		$return_attributes = array();
		for($i=0; $i < count($attributes); $i++) {
			$attribute = trim($attributes[$i]);
			
			if (strlen($attribute) > 0) {
				@list($name, $expression) = explode(':', $attribute);
				$name = trim($name);
				$expression = new CWI_HTML_CSS_Expression(trim($expression));
				array_push($return_attributes, new CWI_HTML_CSS_Declaration($name, $expression));
			}
		}
		return $return_attributes;
	}
	private function parseSelector($selector_text) {
		$selectors = preg_split('/ *, */m', $selector_text);
		$css_selector = new CWI_HTML_CSS_Selector();
		foreach($selectors as $simple_selector) {
			$css_selector->addSimpleSelector( new CWI_HTML_CSS_SimpleSelector($simple_selector) );
		}
		return $css_selector;
	}
	function addRuleSet($css_rule_set) {
		if (is_object($css_rule_set) && is_a($css_rule_set, 'CWI_HTML_CSS_RuleSet')) {
			array_push($this->ruleSets, $css_rule_set);
		}
	}
	function render() {
		$output = '';
		foreach($this->ruleSets as $rule_set) {
			$output .= $rule_set->render();
		}
		return $output;
	}
	function createFromCssText($css_text) {
		FrameworkManager::loadLibrary('html.css.ruleset');
		FrameworkManager::loadLibrary('html.css.selector');
		FrameworkManager::loadLibrary('html.css.simpleselector');
		FrameworkManager::loadLibrary('html.css.declaration');
		FrameworkManager::loadLibrary('html.css.expression');

		$css_doc = new CWI_HTML_CSS_Doc();
		$css_text = CWI_HTML_CSS_Doc::removeUnimportantCode($css_text);

		$token = '{}';
		
		$tok = strtok($css_text, $token);
		$tokenized_css = array();
		
		$element = -1;
		$rules = array();
		while ($tok !== false) {
			$element ++; if ($element > 1) $element = 0;
			$tok = trim($tok);
			
			if ($element == 0) $selector = $tok;
			if ($element == 1) {
				$rule_set = new CWI_HTML_CSS_RuleSet(CWI_HTML_CSS_Doc::parseSelector($selector), CWI_HTML_CSS_Doc::parseDeclarations($tok));
				$css_doc->addRuleSet($rule_set);
			}
			
			$tok = strtok($token);
		}
		
		return $css_doc;
	}
}

?>