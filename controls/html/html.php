<?php

/** 
 * Change Log
 *
 * 04/23/2009	(Robert) Changed getStructKey() to look at $m_name in case structKey is empty.  If name is still empty, function will still return the control ID
 * 05/03/2012	(Robert Jones) removed m_name in favor of making it a parameter
 */
class HtmlControl extends WebControl {
	var $m_tagName;
	var $m_struct; // Associate control to a specific data structure at /data/[m_struct]/[m_struct]_strucutre.php
	var $m_structKey; // Use only when "id"s might conflict on the page

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
	function getTagName() { return $this->m_tagName; }
	function setTagName($name) { $this->m_tagName = $name; }
	
	
	function getName() { return $this->getParam('name'); }
	function setName($name) { $this->setParam('name', $name); }
	function getStruct() { if (!empty($this->m_struct)) return $this->m_struct; else return false; }
	function setStruct($struct) { $this->m_struct = $struct; }
	
	function generateName() {
		$name = $this->getStructKey();
		if ($struct = $this->getStruct()) $name = 'auto[' . $struct . ']['.$name.']';
		$this->setName($name);
	}
	// function joinAllContent() { return true; }
	
	function getStructKey() {
		if (empty($this->m_structKey)) {
			$name = $this->getName();
			if (empty($name) || strpos($name, '[') > 0) {
				return $this->getId();
			} else {
				return $name;
			}
		} else {
			return $this->m_structKey;
		}
	}
}

?>