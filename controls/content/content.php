<?php
class ContentControl extends WebControl {

	var $renderOnRoot = false;
	protected function init() {
		parent::init();
		$this->setInitParams(array(
			'text' => '',
			'placeHolderId' => ''
		));
	}
}