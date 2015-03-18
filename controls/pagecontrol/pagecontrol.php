<?php
/*
	Fascade/Wraps standard control - should only be used by internal processes, and never explicitly
*/
/**
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 */
FrameworkManager::loadLibrary('controls.editable.abstracteditinplacecontrol');
 
class PageControlControl extends CWI_CONTROLS_EDITABLE_AbstractEditInPlaceControl {

	/**
	 * Configuration variables, used by PageControls to display control/window
	 */
	var $m_editMode = 'Default'; // Default | Admin
	var $m_windowMode = 'Internal'; // Internal | Standalone | Full
	var $m_hiddenFields = array();
	protected $localPath;
	
	/**
	 * Configuration variables, used by PageControls to automatically construct/initialize object
	 
	var $m_config = array();
	var $m_configChanged = false;
	*/
	
	/**
	 * Config params to pass to hidden form fields (set in PageControlLogic)
	 */
	var $m_pageControlId;
	var $m_pageId;
	var $m_controlId;
	var $controlFriendlyName;
	var $m_placeholder;
	var $m_sortorder;	
	
	/**
	 * Holds sub control to be rendered
	 */
	var $m_childControl;
	var $m_templateControlObject;
	
	/**
	 * Configuration variables, used by PageControls to automatically construct/initialize object
	 */ 
	var $m_config = array();
	var $m_configPosted = false;
	
	function __construct($init_obj) {
		parent::__construct();
		
		$this->m_config = Control::ParseConfigString($init_obj->config);
		$this->setControlId($init_obj->control_id);
		$this->setPageControlId($init_obj->id);
		$this->setPageId($init_obj->page_id);
		$this->setSortorder($init_obj->sortorder);
		$this->setPlaceholder($init_obj->placeholder);

		if (strlen($this->getId()) == 0) {
			$id = $this->getPlaceholder() . '_' . $this->getPageControlId();
			$this->setId($id);
		}
		
		if (!empty($init_obj->control_src)) {
			include_once(PathManager::translate($init_obj->control_src));
			$class_name = $init_obj->class_name;
		} else {
			
			FrameworkManager::loadLogic('control');
			$control_struct = ControlLogic::getControlById($init_obj->control_id);
			include_once(PathManager::translate($control_struct->file_src));
			$class_name = $control_struct->class_name;
		}
		
		$this->m_childControl = new $class_name($this->m_config);
		if (strlen($this->m_childControl->getId()) == 0) $this->m_childControl->setId($this->getPlaceholder().'_'.$this->getPageControlId());
	}

	public function init() {
		$this->setInitParam('renderNoContent', true);
		parent::init();
	}
	function prepareContent() {
		if ($this->getEditMode() == 'Admin') {
			$this->updateControlConfig();
			if (Page::isPostBack() && $this->m_configPosted) {
			
				$page_control_id			= $this->getPageControlId();
				$page_control_sortorder			= $this->getSortorder();
				
				$page_control_struct			= new PageControlStruct();				
				$page_control_struct->config		= Control::BuildConfigString($this->m_config);
				$page_control_struct->page_id		= $this->getPageId();
				$page_control_struct->placeholder	= $this->getPlaceholder();
				$page_control_struct->control_id	= $this->getControlId();
				
				if (empty($page_control_sortorder)) { // Make sure this PageControl has a sort order
					$page_control_struct->sortorder	= PageControlLogic::getNextSortOrder($page_control_struct->page_id, $page_control_struct->placeholder);
				}
				
				if (!empty($page_control_id)) { // Update Existing Entry
					$page_control_struct->id = $page_control_id;
				}
				
				$page_control_struct = PageControlLogic::save($page_control_struct);
				$this->setPageControlId($page_control_struct->id);
			}
		} else if ($this->getEditMode() == 'Default') {
			$this->setRenderedContent($this->m_childControl->render());
		}
		if (Page::isAdminRequest()) {
			$this->addEditWrap();
		}
	}
	function getOuterId() {
		return $this->m_childControl->getOuterId() . '_container';
	}
	function addEditWrap() {
		$this->addHidden('pagecontrol', $this->getPageControlId());
		$this->addHidden('pageid', $this->getPageId());
		$this->addHidden('placeholder', $this->getPlaceholder());
		$this->addHidden('controltype', $this->getControlId());
		$this->addHidden('editmode', $this->getEditMode());
		$this->addHidden('windowmode', $this->getWindowMode());
		
		$form_id = $this->getOuterId() . 'form';
		
		$output = '<div class="editable-control" id="' . $this->getOuterId() . '">';
		
		if (is_a($this, 'EditableControl')) {
			$output .= '<div class="editable-control-bar"><!--Content Section -->';
			if ($this->getWindowMode() == 'Internal') {
				$output .= '<nobr>';
				if ($this->getEditMode() == 'Default') {
					$output .= '<a href="#" onclick="'.$this->getOuterId().'.changeEditMode(\'Admin\');return false;"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'b_edit.gif" width="47" height="16" border="0" alt="Edit" /></a>';
					$output .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'divider.gif" width="1" height="16" border="0" style="margin:0 5px 0 20px;" />';
				} else {
					$output .= '<a href="#" onclick="'.$this->getOuterId().'.changeEditMode(\'Default\');return false;"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'b_cancel.gif" width="55" height="16" border="0" alt="Cancel" /></a>';
					$output .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'divider.gif" width="1" height="16" border="0" style="margin:0 5px 0 12px;" />';
				}
				$output .= '<a href="#" id="' . $this->getOuterId() . '_linkmoveup"  onclick="' . $this->getOuterId() . '.moveUp();return false"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_pageup.gif" width="16" height="16" border="0" alt="Move Up" /></a>';
				$output .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'divider.gif" width="1" height="16" border="0" style="margin:0 5px;" />';
				$output .= '<a href="#" id="' . $this->getOuterId() . '_linkmoveup"  onclick="' . $this->getOuterId() . '.moveDown();return false"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_pagedown.gif" width="16" height="16" border="0" alt="Move Down" /></a>';
				if ($this->getEditMode() == 'Default') {
					#$output .= '<a href="#" onclick="editControl(\''. $this->getOuterId() .'\', \'' . $this->getPageControlId() . '\', \'Admin\');return false;">Edit</a>';
					#$output .= ' | <a href="#" onclick="window.open(\''.ConfigurationManager::get('DIR_WS_ADMIN') . 'getcontrolmode.html?pagecontrol=' . $this->getPageControlId() . '&editmode=Admin&windowmode=Full&htmlouterid=' . $this->getOuterId() . '&xcontrolid=' . $this->getOuterId() . '\', \'editControl\', \'status=0,toolbar=0,menubar=0,resizable=1,width=800,height=480,location=0,titlebar=0,scrollbars=1\');return false;"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_openwindow.gif" width="16" height="16" border="0" alt="Edit in Window" /></a>';
					$output .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'divider.gif" width="1" height="16" border="0" style="margin:0 5px;" />';
					#$output .= '<a href="#" onclick="'.$this->getOuterId().'.openEditWindow();return false;"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_openwindow.gif" width="16" height="16" border="0" alt="Edit in Window" /></a>';
					$output .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'divider.gif" width="1" height="16" border="0" style="margin:0 5px;" />';
					$output .= '<a href="#" onclick="'.$this->getOuterId().'.removePageControl();return false;"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_delete.gif" width="16" height="16" border="0" alt="Remove" /></a>';
				} else {
					$output .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'divider.gif" width="1" height="16" border="0" style="margin:0 5px;" />';
					$output .= '<a href="#" onclick="'.$this->getOuterId().'.changeEditMode(\'Default\');return false;"><img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_pagedeny.gif" width="16" height="16" border="0" alt="Cancel" /></a>';
				}
				$output .= '</nobr>';
			}
			$output .= '</div>';
		}
		$output .= '<div class="editable-body">';
		$output .= '<form id="' . $form_id . '" method="post" action="' . Page::getPath() . '"';
		if ($this->getWindowMode() == 'Internal') {
			#$output .= ' onSubmit="saveAndUpdateControlForm(\'' . $form_id . '\', \'' . $this->getOuterId() . '\');return false;"';
			$output .= ' onSubmit="'.$this->getOuterId().'.submitForm();return false;"';
		}
		$output .= '>';
		$output .= $this->getHiddenData().$this->getWrapOutput();
		if ($this->getEditMode() == 'Admin') {
			$output .= $this->renderConfig();
			if (!is_a($this->m_childControl, 'EditableControl')) {
				$output .= '<input type="submit" value="Save" />';
			}
		}
		$output .= '</form></div></div>';
		$this->setWrapOutput($output);

		if ($this->getWindowMode() == 'Full') {
			$output = '<html><body style="margin:0;color:#333;"><div style="background-color:#cacaca;color:#333;padding:5px;text-align:center;font-family:verdana;font-size:14px;border-bottom:2px solid #7fceed;"><img src="'.ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'i_edit.gif" width="16" height="16" border="0" alt="Edit" align="absmiddle" /> <strong>Edit Content</strong></div>';
			#if ($display_message = $this->getDisplayMessage()) $output .= '<div style="background-color:#fafaff;border-bottom:1px solid #e1e1fa;font-family:verdana;font-size:12px;padding:10px 5px;color:#ff0000;"><strong>Note:</strong> ' . $this->getDisplayMessage() . '</div>';
			$output .= '<div style="margin:10px;">'.$this->getWrapOutput() . '</div></body></html>';
			$this->setWrapOutput($output);
		}
	}
	
	function updateControlConfig() {
		if ($config_file = PathManager::translate($this->m_childControl->getConfigFilePath())) {
			$config_file_contents = file_get_contents($config_file);
			FrameworkManager::loadLibrary('xml.compile');
			$config_xml = CWI_XML_Compile::compile($config_file_contents);
			$configurables = $config_xml->getPath('/config/configurables/configurable');
			$configs = array();
			foreach($configurables as $configurable) {
				$var = $configurable->getParam('var');
				$internal_var = 'm_' . $var;
				$form_config = Page::get('config', array());
				if (property_exists($this->m_childControl, $internal_var)) {
					if (array_key_exists($var, $form_config)) { // Exists in form post
						$this->setConfigValue($var, $form_config[$var]);
						$this->m_childControl->$internal_var = $this->getConfigValue($var);
						$this->m_configPosted = true;
					} else { // Does not exist in form post
						$this->setConfigValue($var, $this->m_childControl->$internal_var);
					};
				}
			}
		}
	}
	
	function renderConfig() {
		$form_html = '';

		if ($config_file = PathManager::translate($this->m_childControl->getConfigFilePath())) {

			$config_file_contents = file_get_contents($config_file);

			FrameworkManager::loadLibrary('xml.compile');
			$config_xml = CWI_XML_Compile::compile($config_file_contents);
			$configurables = $config_xml->getPath('/config/configurables/configurable');
			#$form_html .= '<form method="post" action="'.Page::getPath().'">';
			foreach($configurables as $configurable) {
				$var = $configurable->getParam('var');
				$type = $configurable->getParam('type');
				$label = $configurable->getParam('label');
				$help_text = $configurable->getParam('helpText');
				
				$form_html .= '<p><strong>'.$label . '</strong><br />';
				//$form_html .= '<em>'.$help_text.'</em><br />';

				switch (strtolower($type)) {
					case 'select':
						
						if ($data_source = $configurable->getParam('dataSource')) {
							FrameworkManager::loadLogic('content');
							$key_field = $configurable->getParam('keyField', 'id');
							$text_field = $configurable->getParam('textField', 'name');
							$data = eval('return ' . $data_source .';');
							$form_html .= '<select name="config['.$var.']">';
							while ($row = $data->getNext()) {
								$form_html .= '<option value="' . $row->$key_field . '"';
								if (isset($this->$var) && ($row->$key_field == $this->$var)) $form_html .= ' selected="true"';
								$form_html .= '>' . $row->$text_field . '</option>';
							}
							$form_html .= '</select>';
						} else if ($options = $configurable->getPath('option')) {
							$form_html .= '<select name="config['.$var.']">';
							foreach($options as $option) {
								$key = $option->getParam('key');
								$text = $option->getParam('text');
								$form_html .= '<option value="' . $key . '"';
								if (isset($this->$var) && ($key == $this->$var)) $form_html .= ' selected="true"';
								$form_html .= '>' . $text . '</option>';
							}
							
							$form_html .= '</select>';
						}
						break;
					case 'input':
						$form_html .= '<input type="text" name="config['.$var.']" value="' . htmlentities($this->getConfigValue($var)) . '" />';
						break;
				}
				$form_html .= '</p>';
	
			}
			#$form_html .= '<p><input type="submit" value="Save Config" /></p>';
			#$form_html .= '</form>';
		}
		return $form_html;
	}
	function isPageControl() { return $this->m_isPageControl; }
	/**
	 * Used only when this control is being generated by a data generated page and when linked to a PageControl
	 */
	function getPageControlId() { return $this->m_pageControlId; }
	function setFormPageControlId($page_control_id) { $this->m_pageControlId = $page_control_id; }
	
	function getLocalPath() { return $this->localPath; }
	function setLocalPath($path) { $this->localPath = $path; }
	
	function getPageId() { return $this->m_pageId; }
	function setPageId($page_id) { $this->m_pageId = $page_id; }
	
	function getControlId() { return $this->m_controlId; }
	function setControlId($control_id) { $this->m_controlId = $control_id; }
	
	function getPlaceholder() { return $this->m_placeholder; }
	function setPlaceholder($placeholder) { $this->m_placeholder = $placeholder; }
	
	function getSortorder() { return $this->m_sortorder; }
	function setSortorder($order) { $this->m_sortorder = $order; }
	
	function getEditMode() { return $this->m_editMode; }
	function setEditMode($mode) { $this->m_editMode = $mode; }
	
	function getWindowMode() { return $this->m_windowMode; }
	function setWindowMode($mode) { $this->m_windowMode = $mode; }
	
	public function getControlFriendlyName() { return $this->controlFriendlyName; }
	public function setControlFriendlyName($name) { $this->controlFriendlyName = $name; }
	
	function addHidden($name, $value) {
		$this->m_hiddenFields[$name] = $value;
	}
	function getHiddenData() {
		$hidden_fields = '';
		foreach($this->	m_hiddenFields as $name=>$value) {
			$hidden_fields .= '<input type="hidden" name="' . $name . '" value="' . $value . '" />' . "\r\n";
		}
		return $hidden_fields;
	}
	
	function getTemplateFile() { return $this->m_templateFile; }
	function setTemplateFile($template_file) { $this->m_templateFile = $template_file; }
	function getTemplatePath() {
		$class_name = get_class($this->m_childControl);
		$key = substr($class_name, 0, strlen($class_name)-7);
		return '~/templates/controls/' . $key . '/';
	}
	
	function setConfigValue($name, $value) { $this->m_config[$name] = $value; }
	function getConfigValue($name) { if (isset($this->m_config[$name])) return $this->m_config[$name]; else return false; }
}

?>