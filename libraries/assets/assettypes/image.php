<?php

/**
 * Created 02/17/2010
 * Asset type for images assets such as JPGs, GIFs, PNGs, etc.
 */
FrameworkManager::loadLibrary('html.form.fileupload');
FrameworkManager::loadLibrary('assets.asset');
class CWI_ASSETS_ImageAsset extends CWI_ASSETS_Asset {
	function renderHtml() {
		$template = '<img src="%s" width="%s" height="%s" border="0" assetId="%s" />';
		return sprintf($template, $this->getWebFilePath(), $this->getProperty('width'), $this->getProperty('height'), $this->getId());
	}
	
	function populatePropertiesFromUpload($upload) {
		if (is_object($upload) && is_a($upload, 'CWI_HTML_FORM_FileUpload')) {
			if ($upload->isFile()) {
				$file_path = $upload->getFSPath();
				if (file_exists($file_path)) {
					
					list($width, $height) = getimagesize($file_path);
					$this->setProperty('width', $width);
					$this->setProperty('height', $height);
					
					return true;
				} else return false;
			} else return false;
		} else return false;
	}
}

?>