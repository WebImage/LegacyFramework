<?php

/**
 * Utility/helper class for url manipulation
 * TODO: Deprecate in favor of using CWI_STRING_Url
 */
#class UrlManipulator {}
class CWI_STRING_UrlManipulator { #extends UrlManipulator { // Kept UrlManipulator for backwards compatability... if nothing breaks then UrlManipulator should be removed

	private static function _parseUrl($url_query) { // Temporarily private in case we need to modify the way this works
		$url_parts = new stdClass();
		
		$base_url	= '';
		$query		= '';
		$parts		= explode('?', $url_query, 2);
		
		if (count($parts) == 1) {
			if (strpos($parts[0], '=') > 0 || strpos($parts[0], '&') > 0) { // Assume this is a query string
				$query = $parts[0];
			} else { // Assume this is a URL
				$base_url = $parts[0];
			}
		} else if (count($parts) == 2) {
			$base_url = $parts[0];
			$query = $parts[1];
		}

		$url_parts->base_url = $base_url;
		$url_parts->query = $query;
		
		return $url_parts;
	}
	
	public static function replaceBaseUrl($url_query, $new_base_url) {
		$url_parts = CWI_STRING_UrlManipulator::_parseUrl($url_query);
		$new_url = $new_base_url;
		if (!empty($url_parts->query)) $new_url .='?' . $url_parts->query;
		return $new_url;
	}
	
	public static function appendUrl($url_query, $append_name, $append_value) {
		
		$url_parts	= CWI_STRING_UrlManipulator::_parseUrl($url_query);
		
		$base_url	= $url_parts->base_url;
		$query		= $url_parts->query;
						
		$var_sets = explode('&', $query);
		$return_value = '';
		$overwritten = false; // If value already in query string, overwrite.  Otherwise, append value to end of string

		for ($s=0; $s < count($var_sets); $s++) {
			$name_value = explode('=', $var_sets[$s], 2);

			$name = $name_value[0];
			$value = '';
			if (isset($name_value[1])) $value = $name_value[1];
			
			if (strlen($name) > 0) {
				if (strlen($return_value) > 0) $return_value .= '&';
				if ($name == $append_name) {
					$return_value .= $append_name . '=' . urlencode($append_value); // Placed here rather than at the end of the function to keep variables in their respective location in the query string
					$overwritten = true;
				} else {
					$return_value .= $name . '=' . $value;
				}
			}
		}
		if (!$overwritten) { // If not overwritten above (which preserves a vars location within the query string), append here
			if (strlen($return_value) > 0) $return_value .= '&';
			$return_value .= $append_name . '=' . urlencode($append_value);
		}

		return $base_url . '?' . $return_value;	
	}
	
	public static function removeVar($url, $var) {
		$url_parts = CWI_STRING_UrlManipulator::_parseUrl($url);
		
		$base_url	= $url_parts->base_url;
		$query		= $url_parts->query;
		
		$var_sets = explode('&', $query);
		$return_value = '';

		for ($s=0; $s < count($var_sets); $s++) {
			$name_value = explode('=', $var_sets[$s], 2);

			$name = $name_value[0];
			$value = '';
			if (isset($name_value[1])) $value = $name_value[1];
			
			if (strlen($name) > 0) {
				if (strlen($return_value) > 0) $return_value .= '&';
				if ($name != $var) {
					$return_value .= $name . '=' . $value; // Placed here rather than at the end of the function to keep variables in their respective location in the query string
					$overwritten = true;
				}
			}
		}
		
		if (!empty($return_value)) $return_value = $base_url . '?' . $return_value;
		return $return_value;
	}
	
	// Takes a URL string and converts the query string to hidden fields 
	public static function makeHidden($url_query) {
		
		$url_parts	= CWI_STRING_UrlManipulator::_parseUrl($url_query);
		$base_url	= $url_parts->base_url;
		$query		= $url_parts->query;
		
		$var_sets = explode('&', $query);

		$hidden_html = '';
		for ($s=0; $s < count($var_sets); $s++) {
			$name_value = explode('=', $var_sets[$s], 2);
			
			$name = $name_value[0];
			$value = '';
			if (isset($name_value[1])) $value = $name_value[1];
			
			if (strlen($name) > 0) {
				$hidden_html .= '<input type="hidden" name="' . $name . '" value="' . urldecode($value) . '" />';
			}
		}
		
		return $hidden_html;
	}
}

?>