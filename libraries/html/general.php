<?php

class Html {
	function createSelect($name, $ilist, $selected='', $key='id', $name='name', $attr=array()) {
		$output = '';
		array_unshift($attr, array('name'=>$name));
		$open_tag = new CWI_XML_Tag('select', $attr);
		$close_tag = new CWI_XML_Tag('select', array(), XMLTAG_CLOSE);
		$output .= $open_tag->render();
		while ($option = $ilist->getNext()) {
			$option_params = array();
			if (is_object($option)) {
				$key = $option->$key;
				$name = $option->$name;
			} else if (is_array($option)) {
				$key = $option[$key];
				$name = $option[$name];
			} else {
				$key = $option;
				$name = $option;
			}
			$option_params['value'] = $key;
			if ($selected == $key) $option_params['selected'] = "true";
			
			$option_tag = new CWI_XML_Tag('option', $option_params);
			$close_option_tag = new CWI_XML_Tag('option', array(), XMLTAG_CLOSE);
			$output .= $option_tag->render() . $name . $close_option_tag->render();
			
		}
		$output .= $close_tag->render();
		return $output;
	}
}

?>