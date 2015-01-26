<?php

interface CWI_JSON_IEncodable {
	// @return string The actual JSON
	public function getJson();
}
abstract class CWI_JSON_Encodable implements CWI_JSON_IEncodable {
	
	function __construct() {}
	
	// Implemented to get the required objects that will be encoded
	protected function getJsonObj() {
		$response = new stdClass();
		return $response;
	}
	
	// utility/helper function 
	public static function extendJsonObj($orig_response, $extend_with) {
		if (!is_object($orig_response)) $orig_response = new CWI_JSON_Encodable();
		$vars = get_object_vars($extend_with);
		foreach($vars as $var_name=>$var_value) {
			$orig_response->$var_name = $var_value;
		}
		return $orig_response;
	}
	
	public function getJson() { return json_encode($this->getJsonObj()); }
}

?>