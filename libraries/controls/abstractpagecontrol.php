<?php

FrameworkManager::loadLibrary('controls.ipagecontrol');

abstract class CWI_CONTROLS_AbstractPageControl extends WebControl implements CWI_CONTROLS_IPageControl {
	
	private $localPath;
	private $pageControlId;
	private $pageControlTitle;
	private $pageId; // Only pageId or pageTemplateId will be set, but not both
	private $pageTemplateId;
	
	private $controlId;
	private $controlFriendlyName;
	private $placeholder;
	private $sortorder;
	
	private $isFavorite = false;
	private $favoriteTitle;
	
	#Page Control
	var $m_class = 'control';
	
	# Control
	private $config;
	private $editMode; // i.e. EDITABLE_EDITMODE_DEFAULT | EDITABLE_EDITMODE_ADMIN
	private $editContext; // i.e. EDITABLE_EDITCONTEXT_PAGE | EDITABLE_EDITCONTEXT_TEMPLATE
	private $windowMode; // i.e. EDITABLE_WINDOWMODE_INTERNAL | EDITABLE_WINDOWMODE_ADMIN | Standalone | Full
	
	// Getters
	public function getLocalPath() { return $this->localPath; }
	public function getPageControlId() { return $this->pageControlId; }
	public function getPageControlTitle() { return $this->pageControlTitle; }
	public function getPageId() { return $this->pageId; }
	public function getControlId() { return $this->controlId; }
	public function getControlFriendlyName() { return $this->controlFriendlyName; }
	public function getPlaceholder() { return $this->placeholder; }
	public function getSortorder() { return $this->sortorder; }
	
	public function getConfig() { return $this->config; }
	public function getConfigValue($name) { return $this->getConfig()->get($name); }

	protected function getRawEditMode() {
		return $this->editMode;
	}

	public function getEditMode() {
		$edit_mode = $this->getRawEditMode();
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
	
	public function isFavorite($true_false=null) {
		
		if (is_null($true_false)) { // Getter
		
			return $this->isFavorite;
			
		} else {
		
			$this->isFavorite = $true_false;
			
		}
		
	}
	
	public function getFavoriteTitle() { return $this->favoriteTitle; }
	
	// Getters not defined in interface
	public function getPageTemplateId() { return $this->pageTemplateId; }
	
	// Setters
	public function setLocalPath($path) { $this->localPath = $path; }
	public function setPageControlId($page_control_id) { $this->pageControlId = $page_control_id; }
	public function setPageControlTitle($title) { return $this->pageControlTitle; }
	public function setPageId($page_id) { $this->pageId = $page_id; }
	public function setControlId($control_id) { $this->controlId = $control_id; }
	public function setControlFriendlyName($name) { $this->controlFriendlyName = $name; }	
	public function setPlaceholder($placeholder) { $this->placeholder = $placeholder; }
	public function setSortorder($order) { $this->sortorder = $order; }
	
	public function setFavoriteTitle($title) { $this->favoriteTitle = $title; }
	
	// Setters not defined in interface
	public function setPageTemplateId($page_template_id) { $this->pageTemplateId = $page_template_id; }
	
	// Constructor
	function __construct($init_array=array()) {
		$this->config = new ConfigDictionary();
		parent::__construct($init_array);
	}
		
	################################
	
	public function getId() {
		$id = parent::getId();
		if (strlen($id) == 0) {
			$id = $this->getPlaceholder() . '_' . $this->getPageControlId();
			$this->setId($id);
		}
		return $id;
	}

	public function setConfig(ConfigDictionary $config) { $this->config = $config; }
	public function setConfigValue($name, $value) { $this->getConfig()->set($name, $value); }

	public function setEditMode($mode) { $this->editMode = $mode; }
	public function setEditContext($context) { $this->editContext = $context; }
	public function setWindowMode($mode) { $this->windowMode = $mode; }
	
	###############################################
	#                                             #
	# The rest of these methods are not part      #
	# of the interface, but probably should be    #
	#                                             #
	###############################################
	
	public function save() {
		FrameworkManager::loadLogic('pagecontrol');
		
		$page_id = $this->getPageId();
		$page_template_id = $this->getPageTemplateId();
		$placeholder = $this->getPlaceholder();
		
		// Don't save this control if it is not dynamic (that is, none of the following fields are defined)
		if (empty($page_id) && empty($page_template_id) && empty($placeholder)) return false;
		
		$page_control = PageControlLogic::createOrUpdatePageControl($this->getPageControlId(), $this->getPageId(), $this->getPageTemplateId(), $this->getPlaceholder(), $this->getSortorder(), $this->getControlId(), $this->getConfig());
		$this->setPageControlId($page_control->id); // If this was just saved we need to update 
	}
	
	public function isNew() {
		return strlen($this->getPageControlId() == 0);
	}
	
//	private function getConfigFileXml() {
//		if (is_null($this->cachedConfigXml)) {
//			$xml_file = $this->getLocalPath() . 'config.xml';
//			if (file_exists($xml_file)) {
//				FrameworkManager::loadLibrary('xml.compile');
//				try {
//					$xml_config = CWI_XML_Compile::compile( file_get_contents($xml_file) );
//				} catch (Exception $e) {
//					$this->cachedConfigXml = false;
//					return false;
//				}
//				$this->cachedConfigXml = $xml_config;
//				return $xml_config;
//			}
//			$this->cachedConfigXml = false;
//			return false;
//		} else return $this->cachedConfigXml;
//	}
//
}

?>