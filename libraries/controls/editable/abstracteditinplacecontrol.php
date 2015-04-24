<?php

FrameworkManager::loadLibrary('controls.editable.general'); // EDITABLE_EDITMODE_* and EDITABLE_WINDOWMODE_* definitions
FrameworkManager::loadLibrary('controls.editable.editablecontroljsonresponse');
FrameworkManager::loadLibrary('controls.ipagecontrol');
FrameworkManager::loadLibrary('json.encodable');
FrameworkManager::loadLibrary('controls.abstractpagecontrol');

abstract class CWI_CONTROLS_EDITABLE_AbstractEditInPlaceControl extends CWI_CONTROLS_AbstractPageControl implements CWI_CONTROLS_IPageControl {

	private $errors = array();
	private $monitoredFields = array(); // Keep track of editable fields that might be changed
	
	#Page Control
	var $m_class = 'control';
	private $pageControlId;
	private $pageControlTitle;
	private $pageId; // Only pageId or pageTemplateId will be set, but not both
	private $pageTemplateId;
	private $controlId;
	private $controlFriendlyName;
	private $placeholder;
	private $sortorder;
	private $localPath;
	
	# Control
	private $config;
	private $editMode; // i.e. EDITABLE_EDITMODE_DEFAULT | EDITABLE_EDITMODE_ADMIN
	private $editContext; // i.e. EDITABLE_EDITCONTEXT_PAGE | EDITABLE_EDITCONTEXT_TEMPLATE
	private $windowMode; // i.e. EDITABLE_WINDOWMODE_INTERNAL | EDITABLE_WINDOWMODE_ADMIN | Standalone | Full
	
	/**
	 * @var boolean $devMode (true = development mode, false = production mode)
	 * While the use of this may expand, the intention of devMode is to allow control over where resources (i.e. javascript, css, etc.) are loaded from
	 * For example, during development files will be stored in the control's directory, whereas in production they will automatically get moved to the asset manager for loading
	 */
	protected $devMode = false;
	
	private $hiddenFields = array(); // fields to be included with post
	
	private $shouldAddControlToolbars = true;
	
	private $cachedConfigXml;
	
	private $templateObjs = array();
	private $editInPlaceControlManager;
	
	#private $jsSaveHandlers = array();
	private $jsClass = 'Control'; // Javascript class name
	
	function __construct($init_array=array()) {
		$this->config = new ConfigDictionary();
		parent::__construct($init_array);
		
		$this->editInPlaceControlManager = new ControlManager();
	}

	public function init() {
		$this->setInitParam('renderNoContent', true);
		parent::init();
	}

	public function isNew() {
		return strlen($this->getPageControlId() == 0);
	}
		
	public function getId() {
		$id = parent::getId();
		if (strlen($id) == 0) {
			$id = $this->getPlaceholder() . '_' . $this->getPageControlId();
			$this->setId($id);
		}
		return $id;
	}
	public function getJsId() { return $this->getId(); }
	########################################
	
	public function getLocalPath() { return $this->localPath; }
	public function getPageControlId() { return $this->pageControlId; }
	
	public function getPageControlTitle() { return $this->pageControlTitle; }
	public function getPageId() { return $this->pageId; }
	public function getPageTemplateId() { return $this->pageTemplateId; }
	public function getControlId() { return $this->controlId; }
	public function getControlFriendlyName() { return $this->controlFriendlyName; }
	public function getPlaceholder() { return $this->placeholder; }
	public function getSortorder() { return $this->sortorder; }
	public function getConfig() { return $this->config; }
	public function getConfigValue($name) { return $this->getConfig()->get($name); }
	
	public function getEditMode() { 
		$edit_mode = $this->editMode;
		if (empty($edit_mode)) {
			if (Page::isAdminRequest() && Roles::isUserInRole('AdmBase')) $edit_mode = EDITABLE_EDITMODE_ADMIN;
			else $edit_mode = EDITABLE_EDITMODE_DEFAULT;
		}
		return $edit_mode;
	}
	public function getEditContext() { return $this->editContext; }
	public function getWindowMode() {
		$window_mode = $this->windowMode;
		if (empty($window_mode)) $window_mode = EDITABLE_WINDOWMODE_INLINE;
		return $window_mode;
	}
	
	protected function shouldAddControlToolbars($true_false=null) {
		if (is_null($true_false)) { // getter
			return $this->shouldAddControlToolbars;
		} else {
			$this->shouldAddControlToolbars = $true_false;
		}
	}
	
	public function anyErrors() { return (count($this->errors) > 0); }
	public function getErrors() { return $this->errors; }
	protected function addError($error_msg) { array_push($this->errors, $error_msg); }
	
	/**
	 * Adds a script to the page's header.
	 * If in devMode then scripts will be loaded via Page::addScriptText(); otherwise they will be added via Page::addScript('/assets/123/controls/managed/')
	 */
	protected function addControlJavascript($local_path) {
		if ($this->devMode) {
			die("DEV MODE: " . $this->getLocalPath() . $local_path);
		} else {
			die("PRODUCTION MODE");
		}
	}
	
	########################################
	
	public function setLocalPath($path) { $this->localPath = $path; }
	public function setPageControlId($page_control_id) { $this->pageControlId = $page_control_id; }
	public function setPageControlTitle($title) { return $this->pageControlTitle; }
	public function setPageId($page_id) { $this->pageId = $page_id; }
	public function setPageTemplateId($page_template_id) { $this->pageTemplateId = $page_template_id; }
	public function setControlId($control_id) { $this->controlId = $control_id; }
	public function setControlFriendlyName($name) { $this->controlFriendlyName = $name; }
	public function setPlaceholder($placeholder) { $this->placeholder = $placeholder; }
	public function setSortorder($order) { $this->sortorder = $order; }
	public function setConfig(ConfigDictionary $config) { $this->config = $config; }
	public function setConfigValue($name, $value) { $this->getConfig()->set($name, $value); }
	
	public function setEditMode($mode) { $this->editMode = $mode; }
	public function setEditContext($context) { $this->editContext = $context; }
	public function setWindowMode($mode) { $this->windowMode = $mode; }
	
	########################################
	/*
	protected function getTemplatePath() {
		return $this->getLocalPath() . 'templates/';
	}
	*/
	
/*
	protected function getTemplateFile($edit_mode, $window_mode) {
		//$default_template = strtolower($this->getEditMode()) . '.tpl';
		$default_template = 'default.tpl';
		return $default_template;
	}
*/
	
	private function addMonitoredField($field) {
		if (!in_array($field, $this->monitoredFields)) {
			array_push($this->monitoredFields, $field);
		}
	}
	private function getMonitoredFields() {
		return $this->monitoredFields;
	}
	
	/**
	 * Generates a custom field name that is unique to this instance of the template
	 */
	public function getControlFieldName($field_name) {
		return $this->getId() . '_' . $field_name;
	}
	
	/**
	 * Replaces template body/text with generated field names that are unique to this instance of the template (since all instances of this control share the same template file)
	 * Example text field: {control_field.content_title} 
	 * 	Replaced with the results from: $this->getControlFieldName('content_title')
	 */
	protected function replaceTemplateFields($template_text) {
		preg_match_all('/\${control_field\.(.+?)\}/', $template_text, $matches);

		if (count($matches[0]) > 0) {
			for($i=0; $i < count($matches[0]); $i++) {
				$unrendered_name = $matches[0][$i];
				$public_name = $matches[1][$i];
				$internal_name = $this->getControlFieldName($public_name);

				$this->addMonitoredField($internal_name);
				$template_text = str_replace($unrendered_name, $internal_name, $template_text);
			}
		}
		
		/*
		$map_strings = array(
			'{page_control.js_id}' => $this->getId()
		);
		*/
		
		return $template_text;
	}
	/*
	private function getTemplateObj() {
		// Template
		$template_path = $this->getTemplatePath() . $this->getTemplateFile($this->getEditMode(), $this->getWindowMode());
		$expanded_template_path = PathManager::translate($template_path);

		// Get control template file
		$file_contents = file_get_contents($expanded_template_path);
		$control_contents = $this->replaceTemplateFields($file_contents);
		
		$template_obj = CompileControl::compile($control_contents);
		return $template_obj;
	}*/
	
	private function getRequestMethodBase() {
		return 'handle' . $this->getEditMode();
	}
	protected function getRequestHandlingMethod() { return $this->getRequestMethodBase() . 'Request'; }
	protected function getRequestPostBackHandlingMethod() { return $this->getRequestMethodBase() . 'RequestPostBack'; }
	
	private function handleConfigPostBack($config, $output_datatype) {
		if (is_a($config, 'Dictionary')) {
			if ($xml_config = $this->getConfigFileXml()) {
	
				if ($configurables = $xml_config->getPath('configurables/configurable')) {
					
					foreach($configurables as $configurable) {
						$var = $configurable->getParam('var');
						if ($config->get($var)) $this->setConfigValue($var, $config->get($var));//isset($config_array[$var])) $this->setConfigValue($var, $config_array[$var]);
					}
				}
			}
		}

		FrameworkManager::loadLogic('pagecontrol');
		$page_control = PageControlLogic::createOrUpdatePageControl($this->getPageControlId(), $this->getPageId(), $this->getPageTemplateId(), $this->getPlaceholder(), $this->getSortorder(), $this->getControlId(), $this->getConfig());
		$this->setPageControlId($page_control->id); // If this was just saved we need to update 
		
		$response = new CWI_CONTROLS_EDITABLE_EditableControlJsonResponse();
		return $response;
	}

	public function handleActionRequest($action, $fields, $output_datatype) {
		/*
		$std = new stdClass();
		$std->status = true;
		$std->name = 'Robert Jones';
		return json_encode($std);
		*/
	}
	
	public function handlePostBack($fields, $output_datatype='') {
		/**
		 * Create new response object
		 **/
		$response = new CWI_CONTROLS_EDITABLE_EditableControlJsonResponse(); // JSON Response
		/**
		 * Make sure the fields object is of type Dictionary
		 **/
		if (!is_a($fields, 'Dictionary')) {
			#$response = new CWI_CONTROLS_EDITABLE_EditableControlJsonResponse();
			// Inform the caller that this is not a valid request
			$response->addError('Invalid fields');
			return $response;
			//return '{"status":false,"error":"Invalid fields"}'; // Invalid request
		}

		
		// Ensure valid configuration object
		$valid_config = false;
		if ($config_fields = $fields->get('config')) {
			if (is_a($config_fields, 'ConfigDictionary')) $valid_config = true;
			else if (is_array($config_fields)) {
				$config_fields = new ConfigDictionary($config_fields); // Convert array to dictionary
				$valid_config = true;
			}
		}
		// Make sure we have a config dictionary
		if (!$valid_config) $config_fields = new ConfigDictionary();
		
		if (empty($output_datatype)) $output_datatype = EDITABLE_DATATYPE_JSON;
		
		$postback_method = $this->getRequestPostBackHandlingMethod();
		
		if (method_exists($this, $postback_method)) { //pass control to inheritted classes post method
			
			$postback_response = $this->$postback_method($fields, $output_datatype); 
			
			// Save config if everything looks okay
			if ($postback_response->isSuccess()) {
				$config_response = $this->handleConfigPostBack($config_fields, $output_datatype);
				$config_response->setPageControlId($this->getPageControlId());
				$postback_response = CWI_JSON_Encodable::extendJsonObj($postback_response, $config_response);
			}
			
			return $postback_response;
			
		} else { // Postback method does not exist, so save configuration to control anyway
			$response = $this->handleConfigPostBack($config_fields, $output_datatype);
			$response->setPageControlId($this->getPageControlId());
			return $response;
		}
	}
	
	protected function addHidden($name, $value) {
		$this->hiddenFields[$name] = $value;
	}
	protected function getHiddenData() {
		$hidden_fields = '';
		foreach($this->hiddenFields as $name=>$value) {
			$hidden_fields .= '<input type="hidden" name="' . $name . '" value="' . $value . '" />' . "\r\n";
		}
		return $hidden_fields;
	}

	private function getConfigFileXml() {
		if (is_null($this->cachedConfigXml)) {
			$xml_file = $this->getLocalPath() . 'config.xml';
			if (file_exists($xml_file)) {
				FrameworkManager::loadLibrary('xml.compile');
				try {
					$xml_config = CWI_XML_Compile::compile( file_get_contents($xml_file) );
				} catch (Exception $e) {
					$this->cachedConfigXml = false;
					return false;
				}
				$this->cachedConfigXml = $xml_config;
				return $xml_config;
			}
			$this->cachedConfigXml = false;
			return false;
		} else return $this->cachedConfigXml;
	}
	
	private function getConfigBar() {
		if (!$xml_config = $this->getConfigFileXml()) return;
		
		$configuration_bar = '';
		
		/*
		if (file_exists('config.xml')) {
			die("TRUE");
		} else die("FALSE");
		*/
		
		// Setup configuration bar
		#$configuration_bar .= '<div class="editable-control-configbar" id="' . $this->getJsId() . '_configbar" style="display:none;_position:absolute;">';
		#$configuration_bar .= '<div class="row">CSS Class:<input type="text" name="config_class" /></div>';
		
		$configurables = $xml_config->getPath('configurables/configurable');
		
		if (is_array($configurables)) {
			
			if (count($configurables) > 0) {
				
				$configuration_bar .= '<div class="editable-control-configbar" id="' . $this->getJsId() . '_configbar" style="display:none;_position:absolute;">';
				
			#$configuration_bar = '<table cellspacing="0" cellpadding="0" border="0" class="detaileditview">';
		
				foreach($configurables as $configurable) {
					$var = $configurable->getParam('var');
					$type = $configurable->getParam('type');
					$label = $configurable->getParam('label');
					$help_text = $configurable->getParam('helpText');
					
					$type_lower = strtolower($type);
					if ($type_lower == 'auto') continue;
					
					#if ($type_lower != 'auto') 
					#$configuration_bar .= '<tr><td colspan="2"><hr /></td></tr><tr><td class="field">'.$label . '</td><td valign="top" class="value">';
					$configuration_bar .= '<div class="row"><label>' . $label . ':</label> ';
					
					#$configuration_bar .= '<em>'.$help_text.'</em><br />';
					#$obj_var = 'm_'.$var;
					switch (strtolower($type_lower)) {
						case 'select':
	
							if ($data_source = $configurable->getParam('dataSource')) {
								FrameworkManager::loadLogic('content');
								$key_field = $configurable->getParam('keyField', 'id');
								$text_field = $configurable->getParam('textField', 'name');
								
								list($logic_class, $function) = explode('::', $data_source);
								
								if ($logic_class != 'self') {
									$framework_logic = strtolower(substr($logic_class, 0, strlen($logic_class)-5));
									FrameworkManager::loadLogic($framework_logic);
									$data = eval('return ' . $data_source .';');
								} else {
									$data = call_user_func(array($this, $function));
								}							
								
								$configuration_bar .= '<select name="config['.$var.']" onchange="' . $this->getJsId() . '.setConfigValue(\'' . $var . '\', this.options[this.selectedIndex].value);">';
								while ($row = $data->getNext()) {
									$configuration_bar .= '<option value="' . $row->$key_field . '"';
									if ($row->$key_field == $this->getConfigValue($var)) $configuration_bar .= ' selected="true"';
									$configuration_bar .= '>' . $row->$text_field . '</option>';
								}
								$configuration_bar .= '</select>';
							} else if ($options = $configurable->getPath('option')) {
								$configuration_bar .= '<select name="config['.$var.']" onchange="' . $this->getJsId() . '.setConfigValue(\'' . $var . '\', this.options[this.selectedIndex].value);">';
								foreach($options as $option) {
									$key = $option->getParam('key');
									$text = $option->getParam('text');
									$configuration_bar .= '<option value="' . $key . '"';
									if ($key == $this->getConfigValue($var)) $configuration_bar .= ' selected="true"';
									$configuration_bar .= '>' . $text . '</option>';
								}
								$configuration_bar .= '</select>';
							}
							break;
						case 'input':
							$configuration_bar .= '<input type="text"  name="configurable['.$var.']" value="';
							$configuration_bar .= $this->getConfigValue($var);
							$configuration_bar .= '" onkeyup="' . $this->getJsId() . '.setConfigValue(\'' . $var . '\', this.value);" />';
							break;
					}
					#if (!$type_lower != 'auto') 
					$configuration_bar .= '</div>';
				}
				#$configuration_bar .= '<tr><td colspan="2"><hr /></td></tr></table>';
				$configuration_bar .= '</div>';
			}
		}
	
		##########################
		
		#$configuration_bar .= '<div class="row">CSS Class:<input type="text" name="config_class" /></div>';
		#$configuration_bar .= '</div>';
		return $configuration_bar;
	}
	
	private function initJavascriptObject() {
		
		if ($this->getEditMode() == EDITABLE_EDITMODE_ADMIN) {
			#$script = '<script type="text/javascript">';
			$script = '';
			if (strlen($this->getControlId()) > 0) {
				
				$script .= 'var ' . $this->getJsId() . ' = new ' . $this->getJsClass() . '(\'' . $this->getJsId() . '\', \'' . $this->getPageControlId() . '\', \'' . $this->getEditMode() . '\', \'' . $this->getEditContext() . '\', \'' . $this->getWindowMode() . '\', ' . $this->getControlId() . ');'."\r\n";
				$template_id = $this->getPageTemplateId();
				if (!empty($template_id)) $script .= $this->getJsId() . '.setTemplateId(' . $template_id . ');' . "\r\n";
				//if (!empty($template_id)) $script .= 'var ' . $this->getJsId() . '.setTemplateId(' . $template_id . ');' . "\r\n";
				// Typicall, getEditContext() will equal EDITABLE_EDITCONTEXT_TEMPLATE, and control_edit_context_template_id will only be set when editing a template in the admin
				/*
				$script .= $this->getJsId() . '.setPageControlId(\'' . $this->getPageControlId() . '\');'."\r\n";
				$script .= $this->getJsId() . '.setEditMode(\'' . $this->getEditMode() . '\');'."\r\n";
				$script .= $this->getJsId() . '.setWindowMode(\'' . $this->getWindowMode() . '\');'."\r\n";
				$script .= $this->getJsId() . '.setControlType(' . $this->getControlId() . ');' . "\r\n";
				*/
				#$script .= $this->getJsId() . '.contentChanged();' . "\r\n";
				// Add to placeholder
				if (strlen($this->getPlaceholder()) > 0) {
					$script .= $this->getPlaceholder() . '.addControl(' . $this->getJsId() . ');'."\r\n";
				}
	
				/*
				foreach($this->jsSaveHandlers as $func) {
					$script .= $this->getJsId() . '.onSave(' . $func . ');';
				}
				*/
				/*$script .= '</script>';*/
				#return $script;
				#$output .= $script;
				Page::addScriptText($script);
			}
		}
	}
	
	protected function addControlToolbars() {
		#echo '<pre>';print_r($this);exit;
		if (!$this->shouldAddControlToolbars()) return;
		/*
		$fields = $this->getMonitoredFields();
		$wrap .= '<script type="text/javascript">';
		foreach($fields as $field) {
			$wrap .= '$(\'#' . $field . '\').bind(\'contentchanged\', function() { $(\'#savebar\').html(\'Saving...\').slideDown(); });';
		}
		$wrap .= '</script>';
		*/
		################################
		
		
		
		/*
		$control_id = $this->getControlId();
		if (!empty($control_id)) {
			$this->addHidden('controlid', $this->getControlId());
		}
		if (Page::get('controlid')) {
			$this->addHidden('controlid', Page::get('controlid'));
		}
		*/
		if ($this->getEditMode() == EDITABLE_EDITMODE_ADMIN) {

			#$this->addHidden('pagecontrol', $this->getPageControlId());
		
			#$this->addHidden('pageid', $this->getPageId());
			#$this->addHidden('placeholder', $this->getPlaceholder());
			#$this->addHidden('controltype', $this->getControlId());
			#$this->addHidden('editmode', $this->getEditMode());
			#$this->addHidden('windowmode', $this->getWindowMode());
			/*
			$options_view_id = 'controloptionsview_' . $this->getOuterId();
			*/
			$output = '';
			
			// Add Javascript for Admin
			
			/*
			$script = '<script type="text/javascript">';
			$script .= 'var ' . $this->getJsId() . ' = new Control(\'' . $this->getJsId() . '\');'."\r\n";
			$script .= $this->getJsId() . '.setPageControlId("' . $this->getPageControlId() . '");'."\r\n";
			$script .= $this->getJsId() . '.setEditMode("' . $this->getEditMode() . '");'."\r\n";
			$script .= $this->getJsId() . '.setWindowMode("' . $this->getWindowMode() . '");'."\r\n";
			$script .= $this->getJsId() . '.setControlType(' . $this->getControlId() . ');' . "\r\n";
			
			// Add to placeholder
			$script .= $this->getPlaceholder() . '.addControl(' . $this->getJsId() . ');'."\r\n";
			$script .= '</script>';
			$output .= $script;
			*/
			#if (Page::isAdminRequest() && Roles::isUserInRole('AdmBase')) {

			if ($this->getWindowMode() == EDITABLE_WINDOWMODE_INLINE) {
				FrameworkManager::loadManager('theme');
				
				$output .= '<div class="editable-control" id="' . $this->getJsId() . '">';
				
					#################
					/**
					 * Set config values
					 */
					$config_values = $this->getConfig()->getAll();
					
					$script_text = '';
					
					$script_text = '';
					if ($config_values->getCount() > 0) {
						
						while ($config_value = $config_values->getNext()) {
							$script_text .= $this->getJsId() . '.initConfigValue(\'' . $config_value->getKey() . '\', \'' . $config_value->getDefinition() . '\');' . "\r\n";
						}
						
					}
					
					if (!empty($script_text)) {
					
						$output .= '<script type="text/javascript">' . $script_text . '</script>';
					
					}
					
					$config_bar_html = $this->getConfigBar();
					
					$output .= '<div class="editable-control-bar"><form id="' . $this->getControlFieldName('config_form') . '">';

						// Append hidden fields
						$output .= $this->getHiddenData();
						
						#$output .= '<div class="editable-control-bar" style="background-color:#bfdff0;padding:8px;"><!--Content Section -->';
		
								#$output .= '<a href="' . ConfigurationManager::get('DIR_WS_ADMIN') .'pagecontrols/edit.html?pagecontrolid=' . $this->getPageControlWrapper()->getFormPageControlId() . '" class="button"><span>Edit Content</span></a>';
								#$output .= '<a href="#" onclick="' . $this->getOuterId().'.removePageControl();return false;"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_delete.gif" width="16" height="16" border="0" alt="Delete" /></a>';
		
							
								#$output .= '<a href="#" id="' . $this->getOuterId() . '_linkmoveup"  onclick="' . $this->getOuterId() . '.moveUp();return false"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_pageup.gif" width="16" height="16" border="0" alt="Move Up" /></a>';
								#$output .= '<a href="#" id="' . $this->getOuterId() . '_linkmoveup"  onclick="' . $this->getOuterId() . '.moveDown();return false"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_pagedown.gif" width="16" height="16" border="0" alt="Move Down" /></a>';
						/*
						<img src="/assets/admin/img/themes/blue/editablecontrols/b_config-off.png" width="24" height="24" border="0" style="margin-right:5px;" />
						<img src="/assets/admin/img/themes/blue/editablecontrols/b_edit-off.png" width="24" height="24" border="0" style="margin-right:5px;" />
						<img src="/assets/admin/img/themes/blue/editablecontrols/b_movedown-off.png" width="24" height="24" border="0" style="margin-right:5px;" />
						<img src="/assets/admin/img/themes/blue/editablecontrols/b_moveup-off.png" width="24" height="24" border="0" style="margin-right:5px;" />
						*/
						$toolbar_buttons = '<div style="height:24px;overflow:hidden;">';
							#$toolbar_buttons .= '<div style="float:left;">';
							#	$toolbar_buttons .= '<a href="#" onclick="' . $this->getJsId() . '.showConfig();return false;"><img src="/assets/admin/img/themes/blue/editablecontrols/b_config-off.png" width="24" height="24" border="0" style="margin-right:5px;" /></a>';
							#$toolbar_buttons .= '</div>';
							
								$toolbar_buttons .= '<div class="left">';
									
									// Only show config button if there is something to configure
									if (strlen(trim($config_bar_html)) > 0) {
										$toolbar_buttons .= '<a href="#" onclick="' . $this->getJsId() . '.toggleShowConfig();return false;"><img src="/assets/admin/img/themes/blue/editablecontrols/b_config-off.png" width="24" height="24" border="0" align="left" style="margin-right:5px;" /></a>';
									}
									
									$toolbar_buttons .= '<div class="editable-toolbar-title">';
										$toolbar_buttons .= $this->getControlFriendlyName();
									$toolbar_buttons .= '</div>';
									/*
									 * Uncomment to add editing 
									 $toolbar_buttons .= '<script type="text/javascript">var t=$(\'.editable-toolbar-title\').clickedit({inputControlDisplay:\'inline\'});t.bind(\'contentChanged\', function() { alert(\'Changed\'); });</script>';
									 **/
									
								$toolbar_buttons .= '</div>';
								
							$toolbar_buttons .= '<script type="text/javascript">setTimeout(function() { if ($(\'#' . $this->getJsId() . '\').width() < 200) {   $(\'.editable-toolbar-title\').fadeIn(\'fast\');   }}, 50);</script>';
							
							$toolbar_buttons .= '<div style="float:right;">';
								$favorite_image = ($this->isFavorite() ? 'b_favorite-on.png' : 'b_favorite-off.png');
								
								#$toolbar_buttons .= '<a href="#" onclick="' . $this->getJsId() . '.favorite();return false;"><img src="/assets/admin/img/themes/blue/editablecontrols/' . $favorite_image . '" width="24" height="24" border="0" id="' . $this->getJsId() . '-favorite" /></a> ';
								$toolbar_buttons .= '<a href="#" onclick="' . $this->getJsId() . '.remove();return false;"><img src="/assets/admin/img/themes/blue/editablecontrols/b_trash-off.png" width="24" height="24" border="0" /></a> ';
								$toolbar_buttons .= '<a href="#" onclick="' . $this->getJsId() . '.moveDown();return false"><img src="/assets/admin/img/themes/blue/editablecontrols/b_movedown-off.png" width="24" height="24" border="0" class="editable-control-move" _style="display:none;" /></a> ';
								$toolbar_buttons .= '<a href="#" onclick="' . $this->getJsId() . '.moveUp();return false"><img src="/assets/admin/img/themes/blue/editablecontrols/b_moveup-off.png" width="24" height="24" border="0" class="editable-control-move" _style="display:none;" /></a> ';
								//$toolbar_buttons .= '<img src="/assets/admin/img/themes/blue/editablecontrols/b_move-off.png" width="24" height="24" border="0" class="editable-control-move-handle" style="cursor:move;display:none;" />';
							$toolbar_buttons .= '</div>';
						$toolbar_buttons .= '</div>';

						$toolbar_buttons = CWI_MANAGER_ThemeManager::wrapWithWrapClassId(Page::getTheme('ADMIN'), $toolbar_buttons, 'editable-control-toolbar');

						$output .= $toolbar_buttons;
		
						#$output .= '</div>';
	
						$action_bar = '<div class="editable-control-actionbar" id="' . $this->getJsId() . '_actionbar" ' . ($this->isNew()?'':' style="display:none;"') . '>';
							$action_bar .= '<a href="#" onclick="' . $this->getJsId() . '.sendForm();$(\'#' . $this->getJsId() . '-actionbar\').hide();return false;"><img src="/assets/admin/img/themes/blue/editablecontrols/b_save.png" width="64" height="29" border="0" alt="Save" /></a> ';
							$action_bar .= '<a href="#" onclick="' . $this->getJsId() . '.revertChanges();$(\'#' . $this->getJsId() . '-actionbar\').hide();return false;"><img src="/assets/admin/img/themes/blue/editablecontrols/b_revert.png" width="74" height="29" border="0" alt="Revert" /></a>';
						$action_bar .= '</div>';
						$output .= $action_bar;
						
						$output .= $config_bar_html;
						
						$update_bar = '<div class="editable-control-waiting" id="' . $this->getJsId() . '_waiting" align="center" style="display:none;">';
							$update_bar .= '<strong><span id="' . $this->getJsId() . '_message"></span></strong><br />';
							#$update_bar .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'busyanim/loadingbar_145x13.gif" width="145" height="13" border="0" align="absmiddle" />';
						$update_bar .= '</div>';
						#$update_bar = CWI_MANAGER_ThemeManager::wrapWithWrapClassId(Page::getTheme(), $update_bar, 'editable-control-waiting');				
						$output .= $update_bar;
					
						/*
						$output .= '<div class="editable-body">
						<p><a href="#" onclick="' .$this->getJsId() . '.setBusy();return false;">Default</a><br />
						<a href="#" onclick="' . $this->getJsId() . '.setBusy(\'Waiting to delete.\');return false;">Waiting to delete.</a><br />
						<a href="#" onclick="' . $this->getJsId() . '.sendForm();return false;">Send Form</a><br />
						<a href="#" onclick="' . $this->getJsId() . '.commitAllEditableFields();return false;">Commit Fields</a></p>';
						*/
						/*
						$output .= '<form id="' . $form_id . '" method="post" action="http://www.cwimage.com/cms/admin/getcontrolmode.html"';
						if ($this->getWindowMode() == EDITABLE_WINDOWMODE_INLINE) {
						#$output .= ' onSubmit="saveAndUpdateControlForm(\'' . $form_id . '\', \'' . $this->getOuterId() . '\');return false;"';
							$output .= ' onSubmit="'.$this->getOuterId().'.submitForm();return false;"';
						}
						$output .= '>';
						$output .= $this->getHiddenData().
						*/
						
					$output .= '</form></div>';
					
						$output .= $this->getWrapOutput();
		
						//$output .='</form>';*/
						#$output .= '</div>';
						
					// Moved 05/16/2012 // $output .= '</form>';
					
				$output .= '</div>';
				
			} else {
				
				$output .= $this->getWrapOutput();
				
			}

			$this->setWrapOutput($output);
		}
		
	}
	protected function loadView($template_file) {
		
		// Template
		if (!in_array(substr($template_file, 0, 1), array('/', '~'))) { // Local file
			$template_path = $this->getLocalPath() . $template_file;
		} else {
			$template_path = PathManager::translate($template_file);
		}
		
		// Get control template file
		$file_contents = file_get_contents($template_path);
		$control_contents = $this->replaceTemplateFields($file_contents);
		
		/*
		$template_obj = CompileControl::compile($control_contents);
		array_push($this->templateObjs, $template_obj);
		
		eval($template_obj->init_code);
		*/
		$this->editInPlaceControlManager->loadControlsFromText($control_contents);
		$this->editInPlaceControlManager->initialize();
		#echo '<pre>';print_r($this);exit;
		$control_fields = $this->editInPlaceControlManager->getControls()->getAll();
		
		while ($control_field = $control_fields->getNext()) {
			
			$control_id = $control_field->getKey();
			$control_obj = $control_field->getDefinition();
			
			#ob_start();
			
			Page::addControl($control_id, $control_obj);
			
			#Custodian::log('control', 'Load control: ' . $control_id, null, CUSTODIAN_DEBUG);
			
			//Don't add this... it's redundant... $this->addControl($control_obj);
		}
		
		return;
	}
	
	public function prepareContent() {
		
		
		#$this->loadView('templates/default.tpl');
		
		#$this->setRenderedContent('test');
		#return;
		#$template_obj = $this->getTemplateObj();

		$request_method =  $this->getRequestHandlingMethod();
		
		#$request_method = $this->getRequestHandlingMethod();
		if ($class = $this->getConfigValue('class')) {
			
			$this->addClass($class);
			
		}
		
		if (method_exists($this, $request_method)) $this->$request_method();
		
		$this->initJavascriptObject();
		
		$this->setRenderedContent($this->editInPlaceControlManager->render());
		$this->addControlToolbars();
		/*
		ob_start();

		#foreach($this->templateObjs as $template_obj) {
		#	eval($template_obj->attach_init_code);
		#	eval($template_obj->render_code);
		#}

		echo $this->editInPlaceControlManager->render();
		$rendered_contents = ob_get_contents();
		ob_clean();
		eval(" ?>" . $rendered_contents . "<?php ");
		$rendered_contents = ob_get_contents();
		ob_end_clean();
		$this->setRenderedContent($rendered_contents);
		$this->addControlToolbars();
		return;
		*/
	}
	
	/**
	 * A simple helper method to add output to the current control
	 **/
	protected function addOutput($text) {
		
		$l = new LiteralControl();
		$l->setText($text);
		$this->addControl($l);
		
	}
	private function getControlAssetFSDir() {
		return ConfigurationManager::get('DIR_FS_ASSETS') . 'controls/' . strtolower(get_class($this)) . '/';
	}
	private function getControlAssetWSDir() {
		return ConfigurationManager::get('DIR_WS_ASSETS') . 'controls/' . strtolower(get_class($this)) . '/';
	}
	/**
	 * Create the asset directory if it does not exist
	 **/
	private function createControlAssetDir() {
		$dir = $this->getControlAssetFSDir();
		if (!file_exists($dir)) return mkdir($dir, 0755, true);
		return true;
	}
	
	/**
	 * Moves a local script file to the asset directory
	 **/
	private function loadAssetFile($file) {
		
		if ($this->createControlAssetDir()) {
			
			/**
			 * First char of file path
			 **/
			$fc = substr($file, 0, 1);
			/** 
			 * Make sure the path is not an absolute or magic (~/assets/) directory
			 **/
			if (in_array($fc, array('/', '~'))) return false;		
			
			$local_file = $this->getLocalDir() . 'assets/' . $file;
			$asset_file = $this->getControlAssetFSDir() . $file;
			
			$local_exists = file_exists($local_file);
			$asset_exists = file_exists($asset_file);
			
			$link = ($local_exists && !$asset_exists);
			if ($link) {
				symlink($local_file, $asset_file);
			}
			
		}
		return false;
	}
	
	/**
	 * Load a script file from the local directory, copy it to the asset folder, and inclue it as a file
	 **/
	protected function addJavascriptFile($file) {
		$this->loadAssetFile($file);
		Page::addScript($this->getControlAssetWSDir() . $file);
	}
	/**
	 * Load a stylesheet file from the local directory, copy it to the asset folder, and include it as a file
	 **/
	protected function addStylesheetFile($file) {
		$this->loadAssetFile($file);
		Page::addStylesheet($this->getControlAssetWSDir() . $file);
	}
	/**
	 * Binds a javascript function name (needs to be defined when the function is loaded)
	 **/
	/*
	protected function onJsControlSave($js_func_handler) {
		array_push($this->jsSaveHandlers, $js_func_handler);
	}
	*/
	/**
	 * @param string $class The name of the javascript class to use to represent the object
	 **/
	protected function jsClass($class, $file=null) {
		if (!empty($file)) $this->addJavascriptFile($file);
		$this->jsClass = $class;
	}
	private function getJsClass() { return $this->jsClass; }
	
	/**
	 * Create a response that can be used for JSON requests
	 * @return CWI_CONTROLS_EDITABLE_EditableControlJsonResponse
	 **/
	protected function createJsonResponse() {
		return new CWI_CONTROLS_EDITABLE_EditableControlJsonResponse();
	}
}

?>