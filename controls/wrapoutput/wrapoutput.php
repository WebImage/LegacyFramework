<?php

/** 
 * The sole purpose of this file for now is to allow theme controls to be built.  There may or may not be further expansions to this class
 */
/**
 * 05/01/2010	(Robert Jones) Added
 */
class WrapOutputControl extends WebControl {
	protected $outputContent; // Only used if the internal content is set explicitly via this->setContent
	/*
	public function __construct($init_array) {
		if (!isset($init_array['id'])) $this->removePassThru('id');
		parent::__construct($init_array);
	}
	*/
	public function setContent($content) {
		$this->outputContent = $content;
	}
	public function getContent() { return $this->outputContent; }
	
	function prepareContent() {
		$content = $this->getContent();
		if (!is_null($content)) { // Try local content, otherwise fall back to parent's control
			$this->setRenderedContent($content);
		} else return parent::prepareContent();
	}
}

?>