<?php

class ErrorRequestHandler extends AbstractRequestHandler {
	function canHandleRequest($request_path=null) {
		return true;		
	}
	function render() {
		header('HTTP/1.0 404 Not Found');
		return 'There was a problem loading the page you requested.' . str_repeat(' ', 512) . "\r\n<!-- Page Not Found -->";
	}
}

?>