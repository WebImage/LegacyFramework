<?php

class EditableElementControl extends WebControl {
	const EDITFIELDTYPE_TEXT = 'text';
	const EDITFIELDTYPE_TEXTAREA = 'textarea';
	const EDITFIELDTYPE_WYSIWYG = 'wysiwyg';
	
	var $m_processInternal = false;
	#var $m_content;
	#var $m_defaultContent = 'Click to edit';
	#var $m_editFieldType;
	#var $m_submitId;
	#var $m_enableEditing = false;
	
	public function init() {
		$this->setInitParam('renderNoContent', true);

		parent::init();
		if (!$this->getParams()->isDefined('defaultContent')) $this->setParam('defaultContent', 'Click to edit');
		if (!$this->getParams()->isDefined('enableEditing')) $this->setParam('enableEditing', 'false');
		if (!$this->getParams()->isDefined('tagName')) $this->setParam('tagName', 'div');
		/*		
		if (isset($init_array['enableEditing']) && is_string($init_array['enableEditing'])) {
			$init_array['enableEditing'] = ($init_array['enableEditing'] == 'true');
		}
		
		parent::__construct($init_array);
		*/
	}
	
	
	/**
	 * Creates an EditableElementControlObject
	 *
	 * @access public
	 * @return EditableElementControl
	 **/	
	public static function createForEditableControl($editable_control_obj, $edit_field_type, $field_name, $enable_editing=false) {
		$editable_element = new EditableElementControl();
		$editable_element->enableEditing($enable_editing);
		$editable_element->setEditFieldType( $edit_field_type );
		$editable_element->setId( $editable_control_obj->getControlFieldName($field_name) );
		$editable_element->setSubmitId($field_name);
		return $editable_element;
	}
		
	/**
	 * @param string|bool $str_true_false - if NULL passed then act like a GETTER, otherwise act as a SETTER.  If a boolean is passed then it will be converted to the text equivalent (i.e. "true" or "false")
	 **/
	public function enableEditing($str_true_false=null) {
		if (is_null($str_true_false)) { // getter
			return ($this->getParam('enableEditing') == 'true'); //$this->m_enableEditing;
		} else {
			//$this->m_enableEditing = $true_false;
			// Convert value to string if necessary
			if (is_bool($str_true_false)) $str_true_false = $str_true_false ? 'true':'false';
			$this->setParam('enableEditing', $str_true_false);
			
		}
	}
	
	//var $m_canChangeTag = false;  Whether the tag can be edited
	// var $options
	/*
		<cms:EditableElement tagName="h1" id="{this.getControlFieldName('id')}" editFieldType="text" canChangeTag="true">
			<settings>
				<elementType>h1</elementType>
				<defaultElementType>h1</defaultElementType>
				<editFieldType>text</editFieldType>
				<canChangeTag>true</canChangeTag>
				<validTags>
					<option key="h1" label="Header 1" />
					<option key="h2" label="Header 2" />
					<option key="h3" label="Header 3" />
					<option key="h4" label="Header 4" />
					<option key="h5" label="Header 5" />
					<option key="h6" label="Header 6" />
				</validTags>
			</settings>
		</cms:EditableElement>
	*/
	public function getTagName() { return $this->getParam('tagName'); }
	public function getContent() { return $this->getParam('content'); }
	public function getDefaultContent() { return $this->getParam('defaultContent'); }
	public function getEditFieldType() { return $this->getParam('editFieldType'); }
	public function getSubmitId() {
		$id = $this->getParam('submitId');
		return (empty($id)) ? $this->getId() : $id;
	}
	
	public function setTagName($tag_name) { $this->setParam('tagName', $tag_name); }
	public function setContent($content) { $this->setParam('content', $content); }
	public function setEditFieldType($edit_field_type) { $this->setParam('editFieldType', $edit_field_type); }
	public function setSubmitId($submit_id) { $this->setParam('submitId', $submit_id); }

	function prepareContent() {
		
		$content = '';
		
		$type = $this->getEditFieldType();
		
		$tag_name = $this->getTagName();
		
		$wrap_output = '<' . $this->getTagName() . '%s>%s</' . $this->getTagName() . '>';
		
		if (empty($tag_name)) return;
		
		$content = $this->getContent();
		
		####################

		if ($this->enableEditing()) {
			
			$existing_class = $this->getClass();
			$classes = array();
			$classes[] = 'editable-element';
			$classes[] = 'editable-element-' . $type;
			$classes[] = 'editable-highlight';
			$classes[] = 'editable-clickable';
	
			if (!empty($existing_class)) $classes[] = $existing_class;
	
			$this->setClass(implode(' ', $classes));
			
			/** 
			 * Build javascript function that can be called to notify the managing control that it needs to keep track of this field for changes
			 */
			$js_obj_name = ''; // Javascript object name
			$js_init_function = 'function() {}';
			
			switch ($type) {
				case 'text':
				case 'textarea':
				case 'wysiwyg':
					/* $wrap_output .= '<script type="text/javascript">makeContainerEditableField(\'' . $this->getId() . '\', \'' . $type . '\', \'' . $this->getSubmitId() . '\');</script>'; */
					/* $wrap_output .= '<script type="text/javascript">$(\'#' . $this->getId() . '\').clickedit({type:\'' . $type . '\', id:\'' . $this->getId() . '_field\', name:\'' . $this->getSubmitId() . '\', onInit:' . $js_init_function . ', placeholder:\'<em>' . $this->getDefaultContent() . '</em>\'});</script>'; */
					$js_editable_config = '{type:\'' . $type . '\', id:\'' . $this->getId() . '_field\', name:\'' . $this->getSubmitId() . '\', onInit:' . $js_init_function . ', placeholder:\'<em>' . $this->getDefaultContent() . '</em>\'}';
					
					#if (preg_match('/(.*?_)([0-9]+|new[0-9]+)_.+/', $this->getId(), $matches)) {
					if (preg_match('/(.*_)([0-9]+|new[0-9]+)_.+/', $this->getId(), $matches)) {
						$js_obj_name = $matches[1] . $matches[2];
						$js_init_function = $js_obj_name . '.addEditableField(\'' . $this->getId() . '\', ' . $js_editable_config . ');';
						$wrap_output .= '<script type="text/javascript">' . $js_init_function . '</script>';
					}
					
					break;
				default:
					$wrap_output = 'Invalid type: ' . $type;
			}
		}
		
		####################
		
		$this->setWrapOutput($wrap_output);
		$this->setRenderedContent($content);
	}
}

?>