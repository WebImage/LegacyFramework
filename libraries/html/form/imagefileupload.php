<?php

FrameworkManager::loadLibrary('html.form.fileupload');

class CWI_HTML_FORM_ImageFileUpload extends CWI_HTML_FORM_FileUpload {
	
	var $m_width, $m_height;
	function populateInfo() {
		if ($this->isFile()) {
			list($this->m_width, $this->m_height) = getimagesize($this->getFSPath());
		}
	}
	function getWidth() { return $this->m_width; }
	function getHeight() { return $this->m_height; }
	
}

class ImageFileUpload extends CWI_HTML_FORM_ImageFileUpload {}