<?php

class LiteralControl extends WebControl {

	var $m_processInternal = false;
	
	var $m_struct; // Only used if used in form - uses content from Page::getStruct
	var $m_structKey; // for use with forms and if m_struct is set
	var $m_htmlTag; // Only used if m_struct is defined;
	
	protected function init() {
		parent::init();
		$this->addPassThru('for');
		$this->setWrapOutput(false);
	}

	function prepareContent() {
		$output = '';
		if ($this->getFor()) $this->m_wrapOutput = '<label%s>%s</label>';
		if ($this->getInnerCode()) $output = $this->getInnerCode();
		if ($this->getStruct()) $output = $this->getProcessStructOutput();
		
		$text = $this->getText();
		if (strlen($text) > 0) $output = $text;
		$this->setRenderedContent($output);
	}
			
	function getFor() { 
		if ($for = $this->getParam('for')) return $for; # New
		return false; # New & Legacy
	}
	
	function getStruct() {
		return $this->getParam('struct');
	}
	
	function getStructKey() {
		$key = $this->getParam('structKey');
		
		if (empty($key)) return $this->getId();
		
		return $key;
	}
	function getText() {
		if ($text = $this->getParam('text')) return $text; # New
	}
	function setFor($control_id) {
		$this->setParam('for', $control_id); # New
	}
	function setText($text) {
		$this->setParam('text', $text); # New
	}
	
	function setStruct($struct) {
		$this->setParam('struct', $struct);
	}
	
	function getProcessStructOutput() {
		if (!empty($this->m_htmlTag)) {
			$this->resetPassThrus();
			$this->setWrapOutput('<h1%s>%s</h1>');
		}
		
		if ($struct = $this->getStruct()) {
			$name = $this->getStructKey();
			if ($var_struct = Page::getStruct($struct)) {
				if (property_exists($var_struct, $name)) {
					return $var_struct->$name;
				}
			}
		}
	}
}

?>