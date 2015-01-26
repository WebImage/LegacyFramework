<?php

class QuickFormBuilder {
	private $fieldTypes;
	public function __construct() {
		$this->fieldTypes = new Dictionary();
	}
	public static function initWithQuickFormXml($xml_quick_form) {
		$form_builder = new QuickFormBuilder();
		##################
		$xml_field_types = $xml_quick_form->getPath('fieldTypes/fieldType');
		#echo '<form method="post"><table border="1">';
		#echo '<tr><th>FieldID</th><th>Input Element Type</th><th>Model Field Type</th><th>LabelID</th><th>LabelTxt</th></tr>';
		
		foreach($xml_field_types as $xml_field_type) {
			
			$field_type_id			= $xml_field_type->getParam('id');
			$input_element_type		= $xml_field_type->getParam('inputElementType');
			$model_field_type		= $xml_field_type->getParam('modelFieldType');
			$label_id			= $xml_field_type->getParam('labelId');
			$label_text			= $label_id;
			#$has_options			= ($xml_field_type->getParam('hasOptions') == 'true');
			#$save_options_separately	= ($xml_field_type->getParam('saveOptionsSeparately') == 'true');
			$builder_icon			= $xml_field_type->getParam('builderIcon');
			
			$option_template = null;
			$option_template_type = null;
			
			if ($xml_option_template = $xml_field_type->getPathSingle('optionTemplate')) {
				$option_template	= $xml_option_template->getData();
				$option_template_type	= $xml_option_template->getParam('type');
			}
			
			if (empty($builder_icon)) $builder_icon = '%DIR_WS_ADMIN_ASSETS_IMG%icons/i_page.gif';
			
			
			if ($xml_label_text = $xml_quick_form->getPathSingle('languages/languagePack[@language="en"]/labels/label[@id="' . $label_id . '"]')) {
				if ($txt = $xml_label_text->getParam('text')) {
					if (!empty($txt)) $label_text = $txt;
				}
			}
			
			if ($field_type_id && $input_element_type && $model_field_type && $label_id) {
				#echo '<hr><pre>' . $xml_field_type->debug() . '</pre>';
			}

			$dict_field = new QuickFormBuilderField();
			#$dict_field->set('field_id', $field_id);
			$dict_field->set('field_type_id', $field_type_id);
			$dict_field->set('input_element_type', $input_element_type);
			$dict_field->set('model_field_type', $model_field_type);
			$dict_field->set('label_text', $label_text);
			#$dict_field->set('has_options', $has_options);
			#$dict_field->set('save_options_separately', $save_options_separately);
			$dict_field->set('builder_icon', CM::getValueFromString($builder_icon));
			$dict_field->set('option_template', $option_template);
			$dict_field->set('option_template_type', $option_template_type);
			
			$form_builder->fieldTypes->set($field_type_id, $dict_field);
			
			/*
			if (!$input_element_type) {
				if ($xml_input = $xml_field_type->getPathSingle('input')) {
					if ($xml_elements = $xml_input->getPath('element')) {
						foreach($xml_elements as $xml_element) {
							$name	= $xml_element->getParam('name');
							$type	= $xml_element->getParam('type');
						}
					}
				}
			}
			
			if (!$model_field_type) {
				if ($xml_model_fields = $xml_field_type->getPathSingle('modelFields')) {
					if ($xml_model_fields = $xml_model_fields->getPath('fields')) {
						foreach($xml_model_fields as $xml_model_field) {
							
						}
					}
				}
			}
			*/
			
		}
		return $form_builder;
		#echo '</table><input type="submit" value="Submit" /></form>';
		###############
	}
	
	public function getFieldTypes() {
		$field_types = $this->fieldTypes->getAll();
		$return = array();

		while ($field_type = $field_types->getNext()) {
			array_push($return, $field_type->getDefinition());
		}
		return $return;
	}
	public function getFieldType($field_type) {
		return $this->fieldTypes->get($field_type);
	}
	public function getField($field_type) {
		return $this->getFieldType($field_type);
		
		if ($field_type = $this->getFieldType($field_type)) {
			$input_element_type = $field_type->get('input_element_type');
			if (class_exists($input_element_type)) {
				#$field_control = new $input_element_type($field_type->get('label_text'), $field_type->get('field_id'));
				$field_control = new $input_element_type($field_type->get('label_text'), $field_type->get('field_type'));
				
				return $field_control;
			}
		}
		return false;
	}
	
}
class QuickFormBuilderField extends Dictionary{
	public function generateField($field_name) {
		$input_element_type = $this->get('input_element_type');
		
		if (class_exists($input_element_type)) {
			$field_control = new $input_element_type($this->get('label_text'), $field_name);
			$field_control->setLabelClass('editable-highlight editable-clickable');
			return $field_control;
		} else return false;
	}
	public function generateFieldTemplate(IFormElement $the_new_field=null) {

		if (is_null($the_new_field)) if (!$the_new_field = $this->generateField('${field_id}')) return false;
		#$the_new_field->setLabelClass('editable-highlight editable-clickable');
		#IFormMultipleChoiceElement
		/*if (is_a($the_new_field, 'IFormMultipleChoiceElement')) {
			
			$options = array();
			
			// Check if existing option or generate temp options
			$init_options = array(
					      'new1' => 'Option #1',
					      'new2' => 'Option #2',
					      'new3' => 'Option #3'
					      );
			foreach($init_options as $key=>$text) {
				$option = new stdClass();
				$option->key = $key;
				$option->text = $text;
				array_push($options, $option);
				#$the_new_field->addChoice($key, $text);
			}
			
			#$json_obj->options = $options;
		}
		*/
		$output = $the_new_field->render();
		$output = '<div id="${field_id}-container" class="field-container f_100">' . $output . '</div>';
		
		return $output;
		
	}
}

?>