<?php

class LiteralControl extends WebControl {
	var $m_text;
	var $m_for; // render as label instead of text, i.e. <label for="firstname">First Name</label>
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
		if (!empty($this->m_for)) return $this->m_for; # Legacy
		return false; # New & Legacy
	}
	function getStruct() { if (!empty($this->m_struct)) return $this->m_struct; else return false; }
		function getStructKey() {
		if (empty($this->m_structKey)) {
			return $this->getId();
		} else {
			return $this->m_structKey;
		}
	}
	function getText() {
		if ($text = $this->getParam('text')) return $text; # New
		return $this->m_text; # Legacy
	}
	function setFor($control_id) {
		$this->setParam('for', $control_id); # New
		$this->m_for = $control_id; # Legacy
	}
	function setText($text) {
		$this->setParam('text', $text); # New
		$this->m_text = $text; # Legacy
	}
	function setStruct($struct) { $this->m_struct = $struct; }
	
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