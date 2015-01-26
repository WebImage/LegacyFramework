<?php
class ContentControl extends WebControl {
	var $m_text, $m_placeHolderId;
	var $renderOnRoot = false;
	/**
	 * Special params
	 * placeHolderId
	 **/
	function __construct($init_array=array()) {
		parent::__construct($init_array);
	}
}
?>