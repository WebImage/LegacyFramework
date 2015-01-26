<?php

/**
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 */

class RemoteRequestLogic {

	public static function getSimpleResponse($url, $post_fields=array(), $post_method="GET") {
		#$url = '';
		if (strtoupper($post_method) == 'GET') {
			/* Build Query String */
			$posting = array();
			foreach($post_fields as $key=>$val) {
				$posting[] = $key.'='.urlencode($val);
			}
			$query_string = implode('&', $posting);
			if (strlen($query_string) > 0) $url .= '?'.$query_string;
		}
		
		/* Send Request */
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if (strtoupper($post_method) == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		}
		if (ConfigurationManager::get('REMOTEREQUEST_IGNORESSLERRORS') == 'true') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		$response = curl_exec($ch);
		curl_close($ch);
		
		return $response;
	}
	
	public static function getXmlResponse($url, $post_fields=array(), $post_method="GET") {
		FrameworkManager::loadLibrary('xml.compile');
		if ($simple_response = RemoteRequestLogic::getSimpleResponse($url, $post_fields, $post_method)) {
			try {
				$xml_response = CWI_XML_Compile::compile($simple_response);
			} catch (CWI_XML_CompileException $e) {
				return false;
			}
			return $xml_response;
		} else {
			return false;
		}
	}
}

?>