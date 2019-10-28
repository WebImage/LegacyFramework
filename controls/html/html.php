<?php

/** 
 * Change Log
 *
 * 04/23/2009	(Robert) Changed getStructKey() to look at $m_name in case structKey is empty.  If name is still empty, function will still return the control ID
 * 05/03/2012	(Robert Jones) removed m_name in favor of making it a parameter
 */
class HtmlControl extends WebControl {

	function __construct($init=array()) {
		parent::__construct($init);
		$this->addPassThru('name');
	}

	protected function init() {
		$this->setInitParam('renderNoContent', true);
		parent::init();
	}
	function prepareHtmlTagFormat() { return true; }
	function prepareHtmlTagAttributes() { return true; }
	function prepareHtmlTagContent() { return true; }

	function prepareContent() {
		$this->generateName();
		$this->prepareHtmlTagFormat();
		$this->prepareHtmlTagAttributes();
		$this->prepareHtmlTagContent();
	}
	function getTagName() { return $this->getParam('tagName'); }
	function setTagName($name) { $this->setParam('tagName', $name); }
	
	
	function getName() { return $this->getParam('name'); }
	function setName($name) { $this->setParam('name', $name); }
	function getStruct() { return $this->getParam('struct'); }
	
	function setStruct($struct) { $this-setParam('struct', $struct); }
	
	function generateName() {
		$name = $this->getStructKey();
		if ($struct = $this->getStruct()) $name = 'auto[' . $struct . ']['.$name.']';
		$this->setName($name);
	}
	// function joinAllContent() { return true; }
	

	function getStructKey() {
	
		$structKey = $this->getParam('structKey');
		
		if ($structKey) return $structKey;
		
		$name = $this->getName();
		if (empty($name) || strpos($name, '[') > 0) {
			return $this->getId();
		} else {
			return $name;
		}
	}
}
