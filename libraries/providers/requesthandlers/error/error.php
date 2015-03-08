<?php

class ErrorRequestHandler extends FileRequestHandler {

	function __construct($page_request=null) {
		parent::__construct($page_request);
		$this->setSystemPath( parent::getSystemPath() . '/errors');
	}

	public function canHandleRequest($internal_url=null) {
		$internal_url = '/404.html';
		if (parent::canHandleRequest($internal_url)) {}
		return true;
	}
	public function render() {
		header('HTTP/1.0 404 Not Found');
		return parent::render();
	}
}

?>