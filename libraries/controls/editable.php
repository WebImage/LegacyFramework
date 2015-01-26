<?php

class CWI_CONTROLS_EDITABLE_EditableControlJsonResponse extends CWI_JSON_Encodable {
	public $success = true;
	private $errors = array();
	private $debug = array();
	/**
	 * @var string $onPostSuccess a javascript function - either function name, or anonymous function with javascript code - that can optionally be executed on post back
	 */
	private $onPostSuccess;
	/**
	 * @var string $onPostError a javascript function - either function name, or anonymouse function with javascript code - that can optionally be executed on post back
	 */
	private $onPostError;
	
	public function isSuccess($true_false=null) {
		if (is_null($true_false)) { // Getter
			return $this->success;
		} else if (is_bool($true_false)) { // Setter
			$this->success = $true_false;			
		}
	}
	
	public function addError($error) {
		$this->isSuccess(false);
		array_push($this->errors, $error);
	}
	public function addDebug($debug) {
		array_push($this->debug, $debug);
	}
	/**
	 * Builds a new object that can be encoded
	 * The reason to have this method instead of directly encoded is so that this method (and inheriting classes) can decide which variables to expose, and which to remain hidden.
	 * For example, in this specific class $this->errors is not included unless there are actually values to be exported
	 */
	protected function getJsonObj() {
		$result = new stdClass();
		$result->success = $this->success;
		
		if (count($this->errors) > 0) {
			$result->errors = $this->errors;
		}
		
		if (count($this->debug) > 0) {
			$result->debug = $this->debug;
		}
		
		$on_post_success = $this->getOnPostSuccess();
		$on_post_error = $this->getOnPostError();
		
		if (!empty($on_post_success)) $result->onPostSuccess = $on_post_success;
		if (!empty($on_post_error)) $result->onPostError = $on_post_error;
		return $result;
	}
	
	public function getOnPostSuccess() { return $this->onPostSuccess; }
	public function getOnPostError() { return $this->onPostError; }
	/**
	 * @param string $function A Javascript function name or anonymous function that is called on a postback's success
	 */
	public function setOnPostSuccess($function) { $this->onPostSuccess = $function; }
	/**
	 * @param string $function A Javascript function name or anonymous function that is called on a postback's failure
	 */
	public function setOnPostError($function) { $this->onPostError = $function; }
	
}

?>