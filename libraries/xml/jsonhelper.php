<?php

class CWI_XML_JsonHelper {
	function getJsonFromXmlTraversal($xml_traversal) {
		#echo htmlentities($xml_traversal->toXml());exit;
		$output_json = '';
		$children = $xml_traversal->getChildren();

		$tag_name = $xml_traversal->getTagName();
		$namespace = $xml_traversal->getNamespace();
		
		if (count($children) > 0) {
			
			$children_tags_json = array();
			$children_data_json = array();
			foreach($children as $child) {
				if (is_a($child, 'CWI_XML_Data')) {
					array_push($children_data_json, $child->render());
				} else {
					if (!isset($children_tags_json[$child->getTagName()])) {
						$children_tags_json[$child->getTagName()] = array();
					}
					array_push($children_tags_json[$child->getTagName()], CWI_XML_JsonHelper::getJsonFromXmlTraversal($child));
				}
			}
			
			// Initiate array to hold all values to be attached to the object
			$all_records = array();
			
			/**
			 * Add all children tags
			 */
			foreach($children_tags_json as $tag_name=>$children) {
				$tag_text = '"' . $tag_name . '":[' . implode(',', $children) . ']';
				array_push($all_records, $tag_text);
			}
			
			/**
			 * Add all data tags as singular _data value/object
			 */
			$data_array = array();
			foreach($children_data_json as $child_data) {
				array_push($data_array, trim($child_data));
			}
			$data_text = str_replace('"', '\"', implode(' ', $data_array));
			if (strlen($data_text) > 0) {
				$data_text = '"_data":"' . $data_text . '"';
				array_push($all_records, $data_text);
			}
			
			/** 
			 * Add all tag parameters as _params
			 */
			$params = $xml_traversal->getParams();
			if (is_array($params) && count($params) > 0) {
				$output_params = array();
				foreach($params as $param_name=>$param_value) {
					array_push($output_params, '"' . $param_name . '":"' . str_replace('"', '\"', $param_value) . '"');
				}
				array_push($all_records, '"_params":{' . implode(',', $output_params) . '}');
				
			}

			$output_json = '{';
			$output_json .= implode(',', $all_records);
			$output_json .= '}';
		
		} else {
			if (empty($tag_name)) { // Data only
				#$output_json .= trim($xml_traversal->getData());
			} else {
				#$output_json .= '{}';
			}
		}
		
		return $output_json;
	}
}

?>