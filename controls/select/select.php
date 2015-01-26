<?php

/**
 * 2011-08-26	(Robert Jones) Added getter/setter for defaultKey and defaultText.  It's amazing I've worked this long without these methods!
 **/
 
FrameworkManager::loadControl('html');

class HtmlDataControl extends HtmlControl {
	/**
	 * Half of this stuff should be down in SelectControl... 
	 */
	var $m_data;
	var $m_dataSource;
	var $_dataSourceType = 'Logic';

	var $m_multiple;
	var $m_size;
	
	var $m_htmlBetweenRows = ''; // i.e. <br />
	
	function __construct($init=array()) {
		parent::__construct($init);
		$this->setData(new Collection());
	}
	
	
	function prepareHtmlTagFormat() {
		#$this->setWrapOutput('<select%s>%s</select>');
	}
	function prepareHtmlTagAttributes() {
		#if (!empty($this->m_multiple)) {
		#	$this->setName($this->getName() . '[]');
		#}
	}
	
	function getDataSource() { return $this->m_dataSource; } 
	function setDataSource($str_source) {
		$this->m_dataSource = $str_source;
	}
	function getData() {
		return $this->m_data;
	}
	function setData($ilist_object) { 
		$this->m_data = $ilist_object;
		$this->m_data->resetIndex(); // In case of reuse, make sure index is reset
	}
	
	function bindData() {
		list($class_name, $method_name) = explode('::', $this->getDataSource());
		$logic = strtolower(str_replace('Logic', '', $class_name));
		FrameworkManager::loadLogic($logic);
		$data = eval('return ' . $this->getDataSource() . ';');

		if (isset($data)) {
			$this->setData($data);
		}
	}
	
	function prepareHtmlTagContent() {
		#
	}
	/*
	if ($struct = $this->getStruct()) {
	if ($var_struct = Page::getStruct($struct)) {
		$load_defaults = false;
		$name = $this->getId();
		if (property_exists($var_struct, $name)) {
			$this->setValue($var_struct->$name);
	$page->template_id = 1;
	*/
	function getDataSourceType() { return $this->_dataSourceType; }
	function setDataSourceType($type) { $this->_dataSourceType = $type; }
	
	function getDefaultKey() { return $this->getParam('defaultKey'); }
	function setDefaultKey($key) { $this->setParam('defaultKey', $key); }
		
	function getDefaultText() { return $this->getParam('defaultText'); }
	function setDefaultText($text) { $this->setParam('defaultText', $text); }
}

class MultipleChoiceControl extends DataWebControl { // Not yet fully integrated with type=select or with autoStruct
	var $_displayType = 'radio'; // {select,checkbox,radio}
	var $m_name;
	var $m_selected = array();
	var $_betweenTemplate = '';
	private $columns = 1; // For radio and checkbox
	
	function __construct($init_array=array()) {
		parent::__construct($init_array);
		
		$this->addPassThru('multiple');
		$this->addPassThru('size');
		$this->addPassThru('name');
		$this->addPassThru('onchange');
	}
	
	function getDisplayType() { return strtolower($this->_displayType); }
	function setDisplayType($type) { $this->_displayType = $type; }
	
	function setNumColumns($cols) { $this->columns = $cols; }
	function getNumColumns() { return $this->columns; }
	
	function setValue($value) {
		if (!is_array($value)) $value = array($value);
		$this->m_selected = $value;
	}
	function setValues($value_array) {
		$this->m_selected = $value_array;
	}
	
	function getSelected() {
		if ($post_val = Page::get($this->getId())) {
			if (!is_array($post_val)) $post_val = array($post_val);
			return $post_val;
		} else {
			return $this->m_selected;
		}
	}
	
	function getName() {
		$name = $this->m_name;
		if (empty($name)) $name = $this->getId();
		return $name;
	}
	function setName($name) { $this->m_name = $name; }
	
	function prepareContent() {
		$display_type = $this->getDisplayType();
		$data = $this->getData();
		$id = $this->getId();
		$output = '';
		if ($display_type == 'select') {
			$this->setWrapOutput('<select%s>%s</select>');
		} else {
			$this->setWrapOutput('');
		}
		
		$selected_values = $this->getSelected();
		
		$row_index = -1;
		
		$num_columns = $this->getNumColumns();
		if (empty($num_columns) || !is_numeric($num_columns)) $num_columns = 1;
		$use_columns = ($num_columns > 1 && $display_type != 'select');
		$column_index = 0;
		$break_point = 0;
		$cur_column = 1;
		if ($use_columns) {
			$output .= '<table cellspacing="0" cellpadding="0" border="0"><tr><td valign="top" style="padding-right:10px;">';
			$break_index = ceil($data->getCount() / $num_columns);
		}
		
		while ($row = $data->getNext()) {
			if ($use_columns) {
				$column_index ++;
				if ($column_index > $break_index) {
					$cur_column ++;
					$output .= '</td><td valign="top"';
					if ($cur_column != $num_columns) $output .= ' style="padding-right:10px;"';
					$output .= '>';
					$column_index = 1;
				}				
			}
			
			$row_index++;
			$row_id = $id . '_' . $row_index;
			$row_name = $id;
			if ($display_type == 'checkbox') $row_name .= '[]';
			$key = $row->id;
			$text = $row->name;

			switch ($display_type) {
				case 'checkbox':
				case 'radio':
					#$format = '<div class="option"><input type="%s" name="%s" id="%s"%s value="%s" /> <label for="%s">%s</label></div>';
					$format = '<div class="option"><input type="%s" name="%s" id="%s"%s value="%s" /> <label for="%s">%s</label></div>';
					
					$sel_attr = (in_array($key, $selected_values)) ? ' checked="true"' : '';
					$output .= sprintf($format, $display_type, $row_name, $row_id, $sel_attr, htmlentities($key), $row_id, $text);
					
					/*
					$output .= '<input type="' . $display_type . '" name="' . $row_name . '" id="' . $row_id . '"';
					if (in_array($key, $selected_values)) $output .= ' checked="true"';
					$output .= ' value="' . htmlentities($key) . '" /> <label for="' . $row_id . '">' . $text . '</label>';
					*/
					/*
					Deprecated:
					if ($data->getCurrentIndex() < ($data->getCount() -1)) $output .= $this->getBetweenTemplate();
					*/
					break;
				case 'select':
					$format = '<option value="%s"%s">%s</option>';
					/*
					$output .= '<option value="' . $key . '"';
					if (strlen($key) > 0 && in_array($key, $selected_values)) $output .= ' selected="true"';
					$output .= '>' . $text . '</option>';
					*/
					$sel_attr = (strlen($key) > 0 && in_array($key, $selected_values)) ? ' selected="true"' : '';
					$output .= sprintf($format, $key, $sel_attr, $text);
					break;
			}
		}
		
		if ($use_columns) $output .= '</td></tr></table>';
		
		$this->setRenderedContent($output);
	}
	/**
	 * Deprecated methods
	 **/
	function getBetweenTemplate() {
		// Between template is deprecated... if it is being used make a note of it 
		Custodian::log('MultipleChoiceControl', 'Using deprecated method getBetweenTemplate()');
		return $this->_betweenTemplate;
	}
	function setBetweenTemplate($between_template) {
		// Between template is deprecated... if it is being used make a note of it 
		Custodian::log('MultipleChoiceControl', 'Using deprecated method setBetweenTemplate()');
		$this->_betweenTemplate = $between_template;
	}
}

class RadioControl extends MultipleChoiceControl {
	var $_displayType = 'radio';
	var $_betweenTemplate = '<br />';
}
class CheckboxControl extends MultipleChoiceControl {
	var $_displayType = 'checkbox';
	var $_betweenTemplate = '<br />';
}

class SelectControl extends HtmlDataControl {
	var $m_selected = array();
	
	protected function init() {
		parent::init();
		if (!$this->getKeyField()) $this->setKeyField('id'); // Set default keyField value
		if (!$this->getTextField()) $this->setTextField('name'); // Set default textField value
	}
	
	function __construct($init=array()) {
		parent::__construct($init);
		$this->addPassThru('multiple');
		$this->addPassThru('size');
		$this->addPassThru('onchange');
	}
	
	function prepareHtmlTagFormat() {
		$this->setWrapOutput('<select%s>%s</select>');
	}
	
	function prepareHtmlTagAttributes() {
		if (!empty($this->m_multiple)) {
			$this->setName($this->getName() . '[]');
		}
	}
	
	function prepareHtmlTagContent() {
		$selected_values = $this->getSelected();
		
		if (strlen($this->getDataSource()) > 0) {
			$this->bindData();
		}
		
		$output = '';
		if (strlen($this->getParam('defaultText')) > 0) {
			$output .= '<option value="' . htmlentities($this->getDefaultKey()) . '"';
			if (in_array($this->getDefaultKey(), $selected_values)) {
				$output .= ' selected="true"';
			}
			$output .= '>' . $this->getParam('defaultText') . '</option>';
		}
		
		$data = $this->getData();
	
		$key_field = $this->getKeyField();
		$text_field = $this->getTextField();

		while ($row = $data->getNext()) {
			$key = $row->$key_field;
			$output .= '<option value="'.$key.'"';
			if (in_array($key, $selected_values)) {
				$output .= ' selected="true"';
			}
			if (!isset($row->$text_field)) {
				echo 'Text Field: ' . $text_field;
				echo '<pre>';print_r($row);exit;
			}
			$output .= '>' . $row->$text_field . '</option>';
		}
		
		$this->setRenderedContent($output);
	}
	
	function setValue($value) {
		$this->m_selected = array($value);
	}
	
	function setValues($values_array) {
		$this->m_selected = $values_array;
	}
	
	function getSelected() {
		$selected_values = array();
		if ($struct = $this->getStruct()) {
			if ($var_struct = Page::getStruct($struct)) {
				$name = $this->getStructKey();

				if (property_exists($var_struct, $name)) {
					if (is_array($var_struct->$name)) {
						$selected_values = array_merge($selected_values, $var_struct->$name);
					} else {
						$selected_values[] = $var_struct->$name;
					}
				}
			}
		} else {
			if (!empty($this->m_selected)) {
				if (!is_array($this->m_selected)) $this->m_selected = array($this->m_selected); // Make sure this is an array
				$selected_values = $this->m_selected;
			} else {
				$selected_values[] = Page::get($this->getId());
			}
		}

		return $selected_values;
	}
	
	public function getKeyField() { return $this->getParam('keyField'); }
	public function getTextField() { return $this->getParam('textField'); }
	
	public function setKeyField($key_field) { $this->setParam('keyField', $key_field); }
	public function setTextField($value) { $this->setParam('textField', $value); }
}

?>