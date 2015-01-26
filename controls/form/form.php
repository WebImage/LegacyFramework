<?php

FrameworkManager::loadLibrary('formbuilder');
FrameworkManager::loadLibrary('xml.compile');
FrameworkManager::loadControl('edit');

class FormControl extends EditControl {
	/**
	 * @property FormBuilder The form builder to which all elements will be added
	 **/
	private $formBuilder;
	
	function __construct($init_array=array()) {
		parent::__construct($init_array);
		#$this->formFieldContainer = new FormElementContainer();
		#$this->formFieldContainer->setOption('labelPosition', LABEL_POS_TABLE);
		#$this->formFieldContainer->setOption('tableCellPadding', 5);
		$this->setFormBuilder(new FormBuilder());
	}
	
	private function getQuickForm() {
		
		// Internal quickform library
		include_once($this->getLocalDir() . 'quickform.php');
		
		// Load caching libraries
		FrameworkManager::loadManager('cache');
		$cache = CWI_MANAGER_CacheManager::getProvider('object');
		
		// Instatiate form XML data
		$xml_quick_form = null;
		
		$file_quickform = $this->getLocalDir() . 'quickform.xml';
		$file_quickform_age = filemtime($file_quickform);
		#echo 'File: ' . $file_quickform_age . '<br />';
		#echo 'File: ' . filemtime('/var/www/vhosts/sparta1.athenacms.com/httpdocs/framework/sites/sandbox/tmp/cache/object/FormControlQuickForm.cache');exit;
		
		// Try to load XML from cache
		if ($cache) $xml_quick_form = $cache->getCacheByKey('FormControlQuickForm', $file_quickform_age);
		
		if (empty($xml_quick_form)) {
			
			#$last_modified = filemtime($path);
			#$cache_age = time() - $last_modified;
			
			try {
				$xml_quick_form = CWI_XML_Compile::compile(file_get_contents($file_quickform));
			} catch (Exception $e) {
				// Do nothing
				$d = new Dictionary(array('file'=>$file_quickform, 'xml_error'=>$e->getMessage()));
				Custodian::log('formcontrol', 'Failed to quickform.  File: ${file}.  XML Error: ${xml_error}', $d);
				return false;
			}
			
			if ($cache) $cache->saveCacheByKey('FormControlQuickForm', $xml_quick_form);
		}
		
		return QuickFormBuilder::initWithQuickFormXml($xml_quick_form);
		
	}
	
	public function handleAdminRequestPostBack($fields) {
		
		FrameworkManager::loadLogic('form');
		FrameworkManager::loadLogic('formfield');
		
		FrameworkManager::loadStruct('form');
		FrameworkManager::loadStruct('formfield');
		
		$response = $this->createJsonResponse();
		
		$dbg = 'Form ID: ' . $fields->get('form_id') . '<br />';
		$dbg .= 'Title: ' . $fields->get('form_title') . '<br />';
		$dbg .= 'Num Fields: ' . $fields->get('form_num_fields') . '<br />';
		
		$form_id	= $fields->get('form_id');
		$form_title	= $fields->get('form_title');
		
		$form_struct	= null;
		$form_fields	= new Dictionary();
		
		$previously_existing_fields = array();
		
		if (substr($form_id, 0, 3) == 'new') { // substr because the value will most likely be a unique string in the format: new-[0-9]+
			
			$form_struct = new FormStruct();
			
		} else {
			
			$form_struct = FormLogic::getFormById($form_id);

			if (!$form_struct) { // Hopefully this never happens
			
				$response->addError('Could not retrieve form: ' . $form_id . '.  Please contact support.');
				return $response;
				
			}
			
			$form_fields = new Dictionary();
			
			$rs_form_fields = FormFieldLogic::getFormFieldsByFormId($form_id);
			
			while ($field = $rs_form_fields->getNext()) {
				$form_fields->set($field->field_id, $field);
				$previously_existing_fields[$field->field_id] = false;
			}
			
		}
		
		$form_struct->name = $form_title;
		
		FormLogic::save($form_struct);
		
		// Commit form id to configuration
		$this->setConfigValue('formId', $form_struct->id);
		// Update the form
		$response->set('formId', $form_struct->id);

		$num_fields = $fields->get('form_num_fields');
		
		if (is_numeric($num_fields)) {
			
			if ($num_fields > 0) {
			
				$dbg .= 'Fields:<br />';
				
				for ($i=0; $i < $num_fields; $i++) {
					
					$field_base = 'form_field' . $i . '_';
					
					$id		= $fields->get($field_base . 'id');
					$var_key	= $fields->get($field_base . 'var_key');
					$type_id	= $fields->get($field_base . 'type_id');
					$label		= $fields->get($field_base . 'label');
					$order		= $fields->get($field_base . 'order');
					$num_choices	= $fields->get($field_base . 'num_choices');
					$num_config	= $fields->get($field_base . 'num_config');
					
					$previously_existing_fields[$id] = true;
					
					$dbg .= $i . ':id: ' . $id . '<br />';
					$dbg .= $i . ':label: ' . $label . '<br />';
					$dbg .= $i . ':var_key: ' . $var_key . '<br />';
					$dbg .= $i . ':type_id: ' . $type_id . '<br />';
					$dbg .= $i . ':order: ' . $order . '<br />';
					$dbg .= $i . ':num_choices: ' . $num_choices . '<br />';
					$dbg .= $i . ':num_config: ' . $num_config . '<br />';
					
					$field_exists = $form_fields->isDefined($id);
					
					if ($field_exists) {
						$field_struct = $form_fields->get($id);
						$choices = json_decode($field_struct->choices);
					} else {
						$field_struct = new FormFieldStruct();
						$choices = array();
					}
					
					$field_struct->config		= '';
					$field_struct->enable		= 1;
					$field_struct->field_id		= $id;
					$field_struct->form_id		= $form_struct->id;
					$field_struct->key		= $var_key;
					$field_struct->label		= $label;
					$field_struct->sortorder	= $order;
					$field_struct->type_id		= $type_id;
					
					$save_choices = array();
					
					if (is_numeric($num_choices) && $num_choices > 0) {
						
						$dbg .= 'Options:<br />';
						for($c=0; $c < $num_choices; $c++) {
							
							$choice_base = $field_base . 'choice' . $c . '_';
							
							$value	= $fields->get($choice_base . 'value');
							$label	= $fields->get($choice_base . 'label');
							
							$dbg .= $choice_base . '<br />';
							$dbg .= $i . ':' . $c . ':value: ' . $value . '<br />';
							$dbg .= $i . ':' . $c . ':label: ' . $label . '<br />';
							//field_choice0_value
							$choice = new stdClass();
							$choice->label = $label;
							$choice->value = $value;
							array_push($save_choices, $choice);
							
						}
						
					}
					
					$field_struct->choices		= json_encode($save_choices);
					
					$save_config = new stdClass();
					
					if (is_numeric($num_config) && $num_config > 0) {
						
						$dbg .= 'Config:<br />';
						for($c=0; $c < $num_config; $c++) {
							
							$config_base = $field_base . 'config' . $c . '_';
							
							$name = $fields->get($config_base . 'name');
							$value = $fields->get($config_base . 'value');
							
							$dbg .= $i . ':' . $c . ':name: ' . $name . ' (' . $config_base . ' = ' . ($fields->get($config_base . 'name') ? 'true':'false') . ')<br />';
							$dbg .= $i . ':' . $c . ':value: ' . $value . '<br />';
							
							// Convert text values back to boolean
							if ($value === 'true') $value = true;
							else if ($value === 'false') $value = false;
							
							if (!empty($name)) {
								$save_config->$name = $value;
							}
							
						}
						
						ob_start();
						echo '<pre>';
						print_r($fields);
						echo '</pre>';
						$dbg .= ob_get_contents();
						ob_end_clean();
						
					}
					
					$field_struct->config = json_encode($save_config);
					
					if ($field_exists) {
						FormFieldLogic::save($field_struct);
					} else {
						FormFieldLogic::create($field_struct);
					}
				}
				
				$dbg .= 'previously_existing_fields: ' . count($previously_existing_fields) . '<br />';
				
				// Check for fields that have been recemoved
				foreach($previously_existing_fields as $field_id=>$found) {
					
					// If the field was not found the postback and the field existed previously then disable it
					if (!$found && ($field_struct = $form_fields->get($field_id))) {
						
						$field_struct->enable = 0;
						FormFieldLogic::save($field_struct);
						
					}
					
				}
				
			} else {
				// Clear out old fields
				Custodian::log('form', 'FormControl - need to clear out old fields');
			}
		}
		
		/*
		form_id
		form_title
		form_num_fields
		form_field0_id
		form_field0_var_key
		form_field0_type_id
		form_field0_order
		form_field0_num_choices
		form_field0_num_config
		form_field0_choice0_value
		form_field0_choice0_label
		form_field0_choice1_value
		form_field0_choice1_label
		form_field0_choice2_value
		form_field0_choice2_label
		form_field1_id
		form_field1_var_key
		form_field1_type_id
		form_field1_order
		form_field1_num_choices
		form_field1_num_config
				*/
				
		/*
		ob_start();
		echo '<pre>';
		print_r($fields);
		$dbg .= ob_get_contents();
		ob_end_clean();
		*/
		Custodian::log('form', $dbg);
		return $response;
	}
	
	public function handleAdminRequest() {
		
		/*
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.js"></script>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
		<script type="text/javascript" src="http://dev.athenacms.com/assets/global/js/jquery/jquery.clickedit.js"></script>
		*/
		// Load global javascript libraries
		#Page::addScript('http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.js');
		#Page::addScript('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js');
		#Page::addScript('http://dev.athenacms.com/assets/global/js/jquery/jquery.clickedit.js');
		
		// Load local javascript
		
		$this->jsClass('FormBuilderControl', 'formbuildercontrol.js?reload=' . date('dHis'));
		
		// Load local css
		$this->addStylesheetFile('formbuildercontrol.css?reload=' . date('Ymd'));
		#$this->addStylesheetFile('formbuilder.css');
		
		$quick_form = $this->getQuickForm();
		$field_types = $quick_form->getFieldTypes();
		
		$form_id = $this->getConfigValue('formId');
		$form_title = '';
		
		$json = new stdClass();
		$json->fieldTypes = new stdClass();
		$json->maxFieldId = 0;
		$json->fields = array();
		
		if (empty($form_id)) {
			$form_id = 'new';
			$field_index = 0;
		} else {
			FrameworkManager::loadLogic('form');
			FrameworkManager::loadLogic('formfield');
			$form = FormLogic::getFormById($form_id);
			$form_title = $form->name;
			$field_index = FormFieldLogic::getMaxFieldIdForForm($form_id);
			
			$rs_fields = FormFieldLogic::getFormFieldsByFormId($form_id);

			while ($field = $rs_fields->getNext()) {
				
				$obj = new stdClass();
				$obj->choices		= json_decode($field->choices);
				$obj->config		= json_decode($field->config);
				$obj->id		= $field->field_id;
				#$obj->form_id		= $field->form_id;
				$obj->varKey		= $field->key;
				$obj->label		= $field->label;
				$obj->order		= $field->sortorder;
				$obj->field_type	= $field->type_id;
				
				// Ensure "choices" is an array
				if (!is_array($obj->choices)) $obj->choices = array();
				// Ensure "config" is an object
				if (!is_object($obj->config)) $obj->config = new stdClass();
				
				array_push($json->fields, $obj);
				
/*
f_id [int]
field_id
var_key
label
field_type
*/
			}
		}
		
		
		foreach($field_types as $field_type) {
			
			$field_type_id = $field_type->get('field_type_id');
	
			$json->fieldTypes->$field_type_id = new stdClass();
			
			$generated_field = $field_type->generateField('${field_id}');
			
			$has_choices = is_a($generated_field, 'IFormMultipleChoiceElement'); // Has choices if it implements IFormMultipleChoiceElement
			
			$json->fieldTypes->$field_type_id->field_type_id = $field_type_id;
			$json->fieldTypes->$field_type_id->label = $field_type->get('label_text');
			$json->fieldTypes->$field_type_id->has_choices = $has_choices;

			$json->fieldTypes->$field_type_id->template = $field_type->generateFieldTemplate($generated_field);
			$json->fieldTypes->$field_type_id->option_template = $field_type->get('option_template');
			$json->fieldTypes->$field_type_id->option_template_type = $field_type->get('option_template_type');
			
		}
		
		$json->formTitle = $form_title;//'New Form Title';
		
		$output = '<form class="editform" id="' . $this->getControlFieldName('form') . '" formId="' . $form_id . '" fieldindex="' . $field_index . '"></form><script type="text/javascript">$(\'.editform\').formedit(' . json_encode($json) . ');</script>';
		$this->addOutput($output);
		
	}
	
	private function getFormSubmissionKey($form_id) {
		$key = $this->getPageControlId() . '-' . $form_id;
		return sha1($key);
	}
	
	private function shouldHandlePostBack($form_id) {
		if (Page::isPostBack()) {
					
			$handle_control = Page::get('handlecontrol');
					
			// Make sure this control is supposed to handle this request
			if ($handle_control == $this->getFormSubmissionKey($form_id)) {
				return true;
			}
		}
		return false;
	}
	
	public function handleDefaultRequest() {
		
		if ($form_id = $this->getConfigValue('formId')) {
			
			$quick_form = $this->getQuickForm();
			$field_types = $quick_form->getFieldTypes();
			#echo '<pre>';print_r($quick_form);print_r($field_types);exit;
			
			FrameworkManager::loadLogic('form');
			FrameworkManager::loadLogic('formfield');
			
			if ($form = FormLogic::getFormById($form_id)) {
				
				$rs_fields = FormFieldLogic::getFormFieldsByFormId($form_id);
				
				$this->addOutput('<h2>' . $form->name . '</h2>');
				
				FrameworkManager::loadLibrary('formbuilder');
				$builder = new FormBuilder();
				
				// Add a hidden element that helps determin that the proper instance of this control handles the form submission
				$builder->addElement(new HiddenElement('handlecontrol', $this->getFormSubmissionKey($form->id)));
				
				// Tracks the fields to be saved
				$process_form = $this->shouldHandlePostBack($form->id);
				$process_fields = array(); 
				
				// Load required classes preemptively
				if ($process_form) {
						
					FrameworkManager::loadLogic('formentry');
					FrameworkManager::loadLogic('formentrydata');
						
					FrameworkManager::loadStruct('formentry');
					FrameworkManager::loadStruct('formentrydata');
					
				}
				
				while ($field_struct = $rs_fields->getNext()) {
					
					$field_type = $quick_form->getFieldType($field_struct->type_id);
					
					$input_element_type = $field_type->get('input_element_type');
		
					if (class_exists($input_element_type)) {
			
						$field_element = new $input_element_type($field_struct->label, 'field' . $field_struct->field_id);
						
						$config = new stdClass();
						if (!empty($field_struct->config)) $config = json_decode($field_struct->config);
						$required = (isset($config->required) && $config->required === true);
						$field_element->isRequired($required);
						
						$has_choices = is_a($field_element, 'IFormMultipleChoiceElement') && !empty($field_struct->choices);
						
						$posted_value = $field_element->getPostedValue();
						
						if ($has_choices) {
							
							$choices = json_decode($field_struct->choices);
							// Track which options are available for selection for validation
							$valid_choices = array();
							
							foreach($choices as $choice) {
								
								$label = $choice->label;
								$value = empty($choice->value) ? $label : $choice->value;
								
								$field_element->addChoice($value, $label);
								
								array_push($valid_choices, $value);
							}
							
							/*
							if (!in_array($x, $valid_choices)) {
								ErrorManager::addError('Invalid selection');
							}
							*/
							
						}
						
						if ($process_form) {
							// Makes it easier to just process everything as if it had multiple vlaues
							if (!is_array($posted_value)) $posted_value = array($posted_value);

							foreach($posted_value as $value) {
								$data = new FormEntryDataStruct();
								$data->field_id = $field_struct->field_id;
								$data->value = $value;
								array_push($process_fields, $data);
							}
						}
						
						#$field_control->setLabelClass('editable-highlight editable-clickable');
						#return $field_control;
						$builder->addElement($field_element);
						
					}
					#$this->addOutput($field_struct->label . ' (' . $rs_fields->getCurrentIndex() . ': ' . $field_struct->type_id . ') = ' . ($quick_form->getFieldType($field_struct->type_id)?'True':'False') . '<br />');
					
				}
				
				$builder->addElement(new SubmitActionElement('Submit'));
				
				$form_processed = false;
				
				if ($process_form) {
					
					if ($builder->validate()) {
						$form_entry = new FormEntryStruct();
						$form_entry->form_id = $form->id;
						$form_entry->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
						$form_entry->page_id = Page::getPageId();
						$form_entry->page_url = Page::getRequestedUrl();
						$form_entry->page_referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
						$form_entry->read = 0;
						$form_entry->stat_id = Page::getPageStatId();
						
						FormEntryLogic::save($form_entry);
						foreach($process_fields as $process_field) {
							
							$process_field->form_entry_id = $form_entry->id;
							FormEntryDataLogic::save($process_field);
							
						}
						
						$form_processed = true;
					} else {
						
						$errors = $builder->getErrors();
						
						foreach($errors as $error) {
							ErrorManager::addError($error);
						}
					}
				}
				
				if ($form_processed) {
					$this->addOutput('<h2>Thank You</h2><p>Your submission has been received.</p>');
				} else {
					$this->addOutput($builder->render());
				}
				
			} else {
				
				Custodian::log(strtolower(get_class($this)), 'Unable to load form: ' . $form_id);
				
			}
		}
		
	}
	/**
	 * Prepares the contents for output
	 **/
	public function _prepareContent() {

		/*
		<form class="editform" id="form1">$('.editform').formedit({fieldTypes:<?php echo json_encode($json); ?>});</form>
		*/
		
		$quick_form = $this->getQuickForm();
		$field_types = $quick_form->getFieldTypes();
		
		$json = new stdClass();
		$json->fieldTypes = new stdClass();
		
		foreach($field_types as $field_type) {
			
			$field_type_id = $field_type->get('field_type_id');
	
			$json->fieldTypes->$field_type_id = new stdClass();
			
			$generated_field = $field_type->generateField('${field_id}');
			
			$has_choices = is_a($generated_field, 'IFormMultipleChoiceElement'); // Has choices if it implements IFormMultipleChoiceElement
			
			$json->fieldTypes->$field_type_id->field_type_id = $field_type_id;
			$json->fieldTypes->$field_type_id->label = $field_type->get('label_text');
			$json->fieldTypes->$field_type_id->has_choices = $has_choices;

			$json->fieldTypes->$field_type_id->template = $field_type->generateFieldTemplate($generated_field);
			$json->fieldTypes->$field_type_id->option_template = $field_type->get('option_template');
			$json->fieldTypes->$field_type_id->option_template_type = $field_type->get('option_template_type');
			
		}
		$json->formTitle = 'New Form Title';
		
		$output = '<form class="editform" id="form' . $this->getParam('formId') . '"></form><script type="text/javascript">$(\'.editform\').formedit(' . json_encode($json) . ');</script>';
		
		$this->setRenderedContent($output);
		
		#$this->setRenderedContent($this->formBuilder->render());
	}	
	/**
	 * Gets the current FormBuilder object
	 *
	 * @access public
	 * @return FormBuilder
	 **/
	public function getFormBuilder() { return $this->formBuilder; }
	/**
	 * Sets the current FormBuilder object
	 *
	 * @access public
	 * @return void
	 **/
	public function setFormBuilder($form_builder) { $this->formBuilder = $form_builder; }
	
	/**
	 * Add an element based on IFormElement
	 *
	 * @access public
	 * @return void
	 **/
	public function addElement(IHtmlElement $element) {
		$this->formBuilder->addElement($element);
	}
}

?>