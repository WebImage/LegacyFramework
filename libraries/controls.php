<?php

// Load required event libraries
FrameworkManager::loadLibrary('event.manager');
FrameworkManager::loadLibrary('event.args');
/**
 * CHANGELOG
 * 11/27/2008	Changed ControlTemplateHelper::parseTemplate() to use "$field" instead of "%s" to replace text in $xml_obj->getParam('format')
 * 01/24/2010	Added Control::removePassThru($pass_thru) to remove a specific pass thru value.  Originally implemented for the "textarea" type for InputControl
 * 05/11/2010	Removed FrameworkManager::loadManager('connection') from header because it is loaded by FrameworkManager::init();
 * 05/13/2010	Modified Control::wrapOutput() to not run if the wrapOutput value is "false"
 * 04/27/2012	Changed old parameter structure to use Dictionary (instead of array); Added event support Control::addControl() - primarily for use in the ControlManager so that it can be notified when objects under it's control are modified
 * 06/07/2012	Moved Control::render() functionality to Control::finalizeContent() to allow content to be generated in advance of render() being called.  This allows other code/controls to perform additional operations once all content has been generated.  For example, ErrorControl and HeaderControl rely on all content processing to be completed before they are able to ouput their final results (using an overridden render() method)
 */
class CWI_EVENT_ControlAddedArgs extends CWI_EVENT_Args {
	private $addedControl;
	function __construct(Control $added_control) {
		parent::__construct();
		$this->addedControl = $added_control;
	}
	public function getAddedControl() { return $this->addedControl; }
}
class Object { }
/**
 * Control parameters used to be passed as an array.  Now they use ConfigDictionary.  ControlConfigDictionary allows the ConfigDictionary object to be accessed as if it were an array
 **/
class ControlConfigDictionary extends ConfigDictionary implements ArrayAccess { // extends  IDictionary {
	/**
	 * Implement methods from ArrayAccess
	 **/
	public function offsetExists($key) { return $this->isDefined($key); }
	public function offsetGet($key) { return $this->get($key); }
	public function offsetSet($key, $value) { $this->set($key, $value); }
	public function offsetUnset($key) { $this->del($key); }
}
class Control extends Object {
	var $m_renderNoContent = false;
	var $m_renderedContent;
	private $contentFinalized = false; // Whether the content has already been rendered for this control (ensures that prepareContent() is only called once)
	
	var $m_params = array();
	var $m_renderedChildContent;
	
	var $m_innerCode; // Only set when $this->m_processInternal = false; (i.e. literal control rather than one whose contents are processed)
	
	public static $context;
	private $visible = true;
	
	/**
	 * Format Variables
	 */ 
	#var $m_class; // css Class
	#var $m_wrapClassId; // class id that can be pulled from a theme file
	/**
	 * Cache Variables
	 */
	var $m_enableCache = false; // Determines whether control should be cached or not
	var $m_cacheLevel = 'Page'; // cacheLevel=Site|Page|Control
	var $m_cacheDir; // Directory where cached control should be stored
	var $m_cacheFile = ''; 
	var $m_cacheTimeout = 10; // Time in seconds to cache file
	// Vary by URL / Params
	
	
	/** 
	 * If this control is at the root of a control structure, 
	 * $m_renderOnRoot determines whether it should be automatically rendered.
	 * For the most part this will be true.  Examples of a time when this would
	 * be set to false include the ContentControl because this is a placeholder 
	 * to be rendered within a an actual PlaceHolder control.
	 * Should be set to false $this->getInitCode()
	 */
	var $renderOnRoot = true;
	var $m_page = null;
	var $m_parent = null;
	var $m_processInternal = true;
	//var $m_renderPassedContents;
	
	/**
	 * Parameters to directly pass through, i.e. <cms:Control class="my_class" /> passes 'class="my_class"' directly through to the rendered tag
	 */
	var $passThru = array('id', 'class', 'style');
	
	/**
	 * Child controls
	 **/
	private $controls;
	/**
	 * The addition of the $parameters property on 2011-09-02 is the beginning of the transition away from using Control::__construct($init_array=array()) and torwards using the $parameters ControlConfigDictionary object
	 * @param ControlConfigDictionary $parameters 
	 **/
	protected $parameters;
	
	private $_inited = false; // keep track of whether parent init() was called
	
	/**
	 * @param Array OR Dictionary $init_array An object to initialize parameters.  A Dictionary object is preferred, but this can be initialied with an associative array
	 **/
	function __construct($init_array=array()) { // Constructor
		// Instantiate control collection (children controls)
		$this->controls = new ControlCollection();
		$this->m_renderedContent = '';
		$this->m_renderedChildContent = '';
		
		/**
		 * Instantiate parameters storage
		 **/
		if (is_a($init_array, 'ControlConfigDictionary')) {
			$this->parameters = $init_array;
		} else if (is_array($init_array)) {
			$this->parameters = new ControlConfigDictionary($init_array);
		} else {
			$this->parameters = new ControlConfigDictionary();
		}
		
		/**
		 * Initialize internal processes
		 **/
		$this->init();
		if (!$this->_inited) throw new Exception(sprintf('%s must call parent::init()', get_class($this)));
		$this->_postInit();
		
		$this->_legacyInit($init_array); // Kept for backwards compatibility.  Will be phased out

	}
	public function isProcessInternalEnabled() { 
		return $this->m_processInternal;
	}
	
	public function visible($visible=null) {
		if (is_null($visible)) {
			return $this->visible;//getParam('visible');
		} else {
			$this->visible = $visible; // TRUE OR FALSE
			$this->setParam('visible', $visible?'true':'false');
		}
	}
	public function addControl($control) { 
		$event_args = new CWI_EVENT_ControlAddedArgs($control);
		// Inform listeners that a control is being added
		// Not currently used:CWI_EVENT_Manager::trigger($this, 'controlAdding', $event_args); 
		$this->controls->add($control); # ADD
		// Inform listeners that a control has been added
		CWI_EVENT_Manager::trigger($this, 'controlAdded', $event_args); 
	}
	public function resetControls() { 
		$this->controls->reset();
	}
	
	public function getControls() { return $this->controls; }
	public function getParam($name) { 
		if (!is_a($this->parameters, 'Dictionary')) {
			#echo 'Invalid<pre>';print_r($this);exit;
		}
		if ($parameter = $this->parameters->get($name)) return $parameter; # New
		if (isset($this->m_params[$name])) return $this->m_params[$name]; # Legacy
		# New / Legagy - Return false if parameter is not found
		return false;
	}
	#function getParams() { return $this->m_params; } # Legacy
	public function getParams() { return $this->parameters; } # New
	public function getRenderedContent() {
		return $this->m_renderedContent;
	}
	/**
	 * The getParameter method is the beginning of the transaction away from using getParam and getParams, as well as the __constuct($init_array)
	 * NOT ACTUALLY USED ANYWHERE CURRENTLY
	 **/
	#function getParameter($name) { return $this->parameters->get($name); }
	#function setParameter($name, $value) { $this->parameters->set($name, $value); }
	
	public function getClass() { return $this->getParam('class'); }
	public function getWrapClassId() { return $this->getParam('wrapClassId'); }
		
	#function getRenderPassedContents() {  return $this->m_renderPassedContents; }
	public function getRenderedChildContent() { return $this->m_renderedChildContent; }
	
	public function setId($id) { $this->setParam('id', $id); }
	public function setRenderedContent($content) { $this->m_renderedContent = $content; }
	#function setRenderPassedContents($contents) { $this->m_renderPassedContents = $contents; }
	public function setRenderedChildContent($content) { $this->m_renderedChildContent = $content; }
	public function setParams($params=array()) {
		$this->m_params = $params;
		$this->parameters = new ControlConfigDictionary($params);
	}
	public function setParam($name, $value) { 
		$this->parameters->set($name, $value); # New
		$this->m_params[$name] = $value; # Legacy
	}
	/**
	 * Same as setParam except that values are only set if they have not been set before
	 * @param unknown $class
	 */
	protected function setInitParams($params) {
		
		if (is_a($params, 'ControlConfigDictionary')) {
			$params = $params->getAll();
			while ($param = $params->getNext()) {
				$this->setInitParam($param->getKey(), $param->getDefinition());
			}
		} else if (is_array($params)) {
			foreach($params as $key=>$value) {
				$this->setInitParam($key, $value);
			}
		} else {
			throw new Exception(sprintf('%s was expecting a ControlConfigDictionary'), __CLASS__);
		}
	}
	protected function setInitParam($name, $value=null) {
		if (!$this->getParams()->isDefined($name)) $this->setParam($name, $value);
	}
	public function setClass($class) { $this->setParam('class', $class); }
	public function addClass($class) { 
		$change_class = $this->getParam('class');
		if (empty($class)) $change_class = $class;
		else $change_class .= ' ' . $class;
		$this->setParam('class', $change_class);
	}
		
	public function setWrapClassId($wrap_class_id) { $this->setParam('wrapClassId', $wrap_class_id); }
	
	public function getId() { 
		return $this->getParam('id');
	}
	public function getOuterId() { return $this->getId(); }
	
	/**
	 * Allows controls to share access to exposed variables
	 *
	 * Example:
	 * <cms:Pages>
	 * 	<cms:PageName />
	 * </cms:Pages>
	 *
	 * class PagesControl {
	 * ...
	 * 	function __init($init_array) {
	 * 		$this->getContext()->set('current_page_title');
	 *	}
	 * ...
	 * }
	 *
	 * class PageName {
	 * ...
	 * 	function __init($init_array) {
	 * 		$this->getContext()->get('current_page_title');
	 *	}
	 * ...
	 * }
	 *
	 **/
	public function getContext() {
		#if (is_null(self::$context)) self::$context = new Dictionary();
//		if (is_null($this->context)) $this->context = new Dictionary();
		#return self::$context;		
		$context = Page::getCurrentPageRequest()->getPageResponse()->getContext();
		return $context;
	}
	
	protected function init() {
		$this->visible = !($this->getParam('visible') == 'false');
		$this->_inited = true;
	}
	/**
	 *  After init is called in all inherriting controls, make sure initial values exist for these parameters
	 */
	private function _postInit() {
		/**
		 * Format used to wrap generated content.  Must always contain two %s, the first for parameters and the second for the actual content generated by render()
		 */
		$this->setInitParam('wrapOutput', '<div%s>%s</div>');
	}
	
	/**
	 * An old version of init() that sets objects directly... this is being phased out in favor of using the parameter Dictionary
	 **/
	protected function _legacyInit($params=array()) { // First step is lifecycle
	
		$legacy_fields = array(); // Keep track of legacy fields
		
		if (is_array($params)) { // Prepare for transition into making params Dictionary object instead of array

			foreach($params as $attr_name=>$attr_val) {
				if (!empty($attr_name)) {
					$var_name = 'm_'.$attr_name;
					if (isset($this->$var_name)) array_push($legacy_fields, $var_name); // If this field already exists then it is a legacy field.... trying to phase out all $m_[varname] variables
					$this->$var_name = $attr_val;
				}
			}
			
		} else if (is_a($params, 'Dictionary')) { // If params is passed as a Dictionary, then work backwords to temporarily create property values
			
			$fields = $params->getAll();
			while ($field = $fields->getNext()) {
				$attr_name = $field->getKey();
				$attr_val = $field->getDefinition();
				
				$var_name = 'm_' . $attr_name;
				if (isset($this->$var_name)) array_push($legacy_fields, $var_name); // If this field already exists then it is a legacy field.... trying to phase out all $m_[varname] variables
				$this->$var_name = $attr_val;				
				
			}			
			
		}
		
		/**
		 * Report legacy fields if there are any
		 **/
		if (count($legacy_fields) > 0) {
			$d = new Dictionary(array('class'=>get_class($this), 'legacy_fields'=>implode(', ', $legacy_fields)));
			Custodian::log('controls', 'The class ${class} is still using legacy parameters ${legacy_fields}', $d);
		}
		/*
		if (is_string($this->m_visible)) {
			$this->m_visible = ($this->m_visible == 'true') ? true:false; // Make sure to process as true BOOLEAN
		}*/
		
		/*
		if (!empty($this->m_class) && strpos($this->m_class, '>') > 0) {
			$classes = explode('>', $this->m_class);
			$this->m_class = $classes[0];
			array_shift($classes);
			$this->_additionalClassWrap = $classes;
		}
		*/
	}
	
	public function prepareContent() {} // Override
	public function preRender() { return true; } // Override
	public function preRenderChildren() { return true; }

	public function onRenderChildren() { return true; }
	/*
	public function joinAllContent() { 
		$content = $this->getRenderedContent() . $this->getRenderedChildContent();// . $this->getRenderPassedContents();
		$this->setRenderedContent($content);
	}
	*/
	public function wrapOutput($output) {
		#$rendered_content = '';
		
		$should_wrap_output = $this->getWrapOutput() != 'false' && $this->getWrapOutput() !== false;

#echo 'Should Wrap: ' . $this->getWrapOutput() . PHP_EOL . PHP_EOL;
#return $output;
		#Removed in favor of checking $output string length: if ($should_wrap_output && ( (strlen($this->getWrapOutput()) > 0 && strlen($this->getRenderedContent()) > 0) || $this->m_renderNoContent)) {	
		if ($should_wrap_output && ( (strlen($this->getWrapOutput()) > 0 && strlen($output) > 0) || $this->m_renderNoContent)) {	
			// Get the wrap format for the sprintf statement below
			$wrap_output_format = $this->getWrapOutput();
			
			// Get the CSS class to use
			$class = $this->getClass();
			
			// Get the CSS class ID that can be used to lookup a css class series (i.e. class1>class2>class3) and override $class
			$wrap_class_id = $this->getWrapClassId();

			// Override $class if necessary
			if (!empty($wrap_class_id)) {
				FrameworkManager::loadManager('theme');
				$class = CWI_MANAGER_ThemeManager::getWrapClassById(Page::getTheme(), $wrap_class_id);
				if (empty($class)) {
					$param_string = $this->getParamString();
				} else {
					$this->removePassThru('class'); // Removes "class" from the pass thru list so that a "class" value is not appended to the parameter list
					$param_string = $this->getParamString() . ' class="' . $class . '"';
				}
			} else {
				$param_string = $this->getParamString();
			}
			
			// Wrap outputted content with additional css class wraps
			/**
			 * Allows multiple classes to be specified via the "class" attribute, this way CSS class can be layered for creative effect.
			 * Right now the only supported method of doing this is via the greater than (>) sign:
			 * class="outer>inner1>inner2" where "outer" becomes the main class, and inner1 and inner2 are then added to _additionalClassWrap.
			 * On render, these classes are then wrapped around the rendered content, in this case the rendered output would look something like:
			 * <div class="outer"><div class="inner1"><div class="inner2">test</div></div></div>
			 */
			 
			if (!empty($class) && strpos($class, '>') > 0) {
				$classes = explode('>', $class);
				$param_string = str_replace('class="' . $class . '"', 'class="' . $classes[0] . '"', $param_string);

				array_shift($classes);
				foreach($classes as $wrap_class) {
					$wrap_output_format = sprintf($wrap_output_format, '%s', '<div class="' . $wrap_class . '">%s</div>');
				}
			}
			
			#$this->setRenderedContent(sprintf($wrap_output_format, $param_string, $this->getRenderedContent()));
			return sprintf($wrap_output_format, $param_string, $output);
		}
		return $output;
	}
	
	/**
	 * Returns a space concatenated string of parameters and their values in the format name="value"
	 **/
	public function getParamString() {
		// 
		$s_params = '';
		
		$pass_thrus = $this->getPassThrus();
		$pass_thrus_used = array(); // Keep track of pass thru values for legacy support
		$params = $this->getParams()->getAll();
		
		// Iterate through list of parameters...
		while ($param_def = $params->getNext()) {
			
			$name = $param_def->getKey();
			$value = $param_def->getDefinition();
			
			// ... check whether the parameter is in the list of parameters to be output
			if (in_array($name, $pass_thrus)) {
				
				$s_params .= ' ' . $name . '="' . $value . '"';
				$pass_thrus_used[] = $name;
				
			}
			
		}
		/**
		 * Begin legacy support
		 **/
		// Legacy support - need to phase this out
		$legacy_fields = array();
		foreach($pass_thrus as $pass_thru) {
			
			$param_name = 'm_'.$pass_thru;
			
			// Make sure this pass through field has not already been added in the new version above
			if (!in_array($pass_thru, $pass_thrus_used)) {
				
				if (isset($this->$param_name)) {
				
					$s_params .= ' ' . $pass_thru . '="' . $this->$param_name . '"';
					
				}
				
				array_push($legacy_fields, $param_name);
			}
		}
		
		/**
		 * End legacy support
		 **/
		return $s_params;
	}
	
	protected function page() {} // Return reference to calling page
	protected function parent() {} // Return parent

	public function setRenderNoContent($true_false) {
		$this->m_renderNoContent = $true_false;
	}
	#function render($auto_init=true) {
		
	/**
	 * Finalize content preparation.  When working directly with a control, e.g. $control = new MyControl(), this will be called via $control->render() directly.  When being worked with as part of a ControlManager, finalizeContent() will be called prior to render being called.  Since this method is called in render() we utilize "$this->contentFinalized" to make sure this method is not executed twice
	 **/
	public function finalizeContent() {
		#$output = '';
		if ($this->visible() && !$this->contentFinalized) {
			$this->contentFinalized = true;
			
			/* Check for Cached Version */
			if ($cached_output = $this->getCachedOutput()) return $cached_output;

			$this->prepareContent(); // Override to set content
			$this->preRender(); // Run after content is set for this control only, but before content is returned
			$this->renderChildren(); // Get content for children controls
			
			#$output = $this->getRenderedContent();

			#$this->cacheOutput($output);
		}
	}
	
	public function contentFinalized() {} // Called after all content is finalized
	
	public function render() {
		$this->finalizeContent();
		$this->contentFinalized(); // Called after all regular content is finalized, allowing for post manipulation of content
		#$this->joinAllContent(); // Join all prepared content together, including child controls, passed content, and prepared content
		#$content = $this->getRenderedContent() . $this->getRenderedChildContent();// . $this->getRenderPassedContents();
		#$this->setRenderedContent($content);
		
		#$this->wrapOutput();
		
		return $this->wrapOutput($this->getRenderedContent() . $this->getRenderedChildContent());
		
	}
	public function renderDirect() { //  Allows direct rendering of tree of controls
		return $this->render(false);
	}
	protected function _getCacheFile() {
		switch ($this->getCacheLevel()) {
			case 'Site':
			case 'Page':
			case 'Control':
			default:
				$path = preg_replace('/[\/\.]/', '~', substr($_SERVER['PHP_SELF'], 1, strlen($_SERVER['PHP_SELF'])-1));
				$cache_file = $this->getCacheDir() . $path . '#'.$this->getId();
			break;
		}
		return $cache_file;
	}
	protected function cacheOutput($output) {
		if ($this->enableCache()) {
			$cache_file = $this->_getCacheFile();

			if ($fp = fopen($cache_file, 'w')) {
				$output = preg_replace('/\n\t+/', '', $output);
				fwrite($fp, $output, strlen($output));
				fclose($fp);
			}
		} else return false;
	}
	protected function getCachedOutput() {
		if ($this->enableCache()) {
			$cache_file = $this->_getCacheFile();

			if (@file_exists($cache_file)) {
				$last_modified = filemtime($cache_file);
				$cache_age = time() - $last_modified;
				if ($cache_age < $this->getcacheTimeout()) {
					ob_start();
					readfile($cache_file);
					$output = ob_get_contents();
					ob_end_clean();
					return $output;
				} else return false;
			} else return false;
		} else return false;
	}
	
	public function display() { echo $this->render(); }
	
	// Cache Get Functions
	public function getCacheLevel() { return $this->m_cacheLevel; }
	public function getCacheDir() { 
		if (empty($this->m_cacheDir)) {
			return ConfigurationManager::get('DIR_FS_CACHE');
		} else {
			return $this->m_cacheDir;
		}
	}
	
	public function getcacheTimeout() { return $this->m_cacheTimeout; }
	
	// Get wrap output format (see setWrapOutput())
	public function getWrapOutput() { return $this->getParam('wrapOutput'); }
	
	// Cache Set Functions
	public function setCacheLevel($cache_level) { $this->m_cacheLevel = $cache_level; }
	public function setCacheDir($cache_dir) { $this->m_cacheDir = $cache_dir; }
	public function setCacheFile($cache_file) { $this->m_cacheFile = $cache_file; }
	public function setCacheTimeout($cache_time) { $this->m_cacheTimeout = $cache_time; }
	
	// Set wrap output format.  Must contain two placing holding "%s" for sprintf() function.  The first is the set of parameters and the second is the inner contents.  Generally this will mean <htmltag%s>%s</htmltag>
	public function setWrapOutput($wrap_output) { $this->setParam('wrapOutput', $wrap_output); }
	
	public function enableCache($enable=null) {
		if (is_null($enable)) { // Act as GET property
			if (is_string($this->m_enableCache)) {
				return (bool)$this->m_enableCache;
			} else {
				return $this->m_enableCache;
			}
		} else { // Act as SET property
			$this->m_enableCache = $enable;
		}
	}
	
	public function renderOnRoot($true_false=null) {
		if (is_null($true_false)) {
			return $this->renderOnRoot;
		} else {
			$this->renderOnRoot = $true_false;			
		}
	}
	
	#function renderChildren($auto_init=true) {
	public function renderChildren() {
		$children = $this->getControls();
		$children->sort();
		#if ($children->getCount() > 0) {
		#	echo '<pre>';print_r($children);exit;
		#}
		$tmp_content = '';
		if ($this->preRenderChildren()) {

			while ($control = $children->getNext()) {

				$tmp_content .= $control->render();

			}

		} else {
			// False
		}
		$this->setRenderedChildContent($tmp_content);
		return $this->m_renderedChildContent;
	}
	
	protected function getControlsByType($type) {}
	
	public function &getControlById($id) {
		if ($this->getId() == $id) {
			return $this;
		} else {
			#$children = &$this->getControls();
			$children = $this->getControls();
			
			#for ($c=0; $c < count($children); $c++) {
			while ($child = $children->getNext()) {
				#if ($control = &$children[$c]->getControlById($id)) {
				if ($control = $child->getControlById($id)) {
					return $control;
				}
			}
			
			return false;
		}
	}
	
	protected function &getSelf() { return $this; }
	
	public function hasControls() { return ($this->controls->count > 0); }
	
	public function debugOutput($output) {
		return;
		if (isset($_GET['debug'])) {
			
			echo $output;
			
		}
		
	}
	
	public function getInitCode($build_for_page=true) { // Only used by CompileControl::compile() - should not be used by anything else
		
		self::debugOutput('<hr /><strong>' . get_class($this) . '->getInitCode(' . ($build_for_page?'true':'false') . ')</strong><br />');
		
		$init_code = '';
		$attach_init_code = '';

		if (strlen($this->getId()) > 0) {
			$this_id = $this->getId();
			$this->setId($this_id);
			self::debugOutput('strlen($this->getId()) > 0) = ' . $this_id . '<br />');
		}
		
		if (isset($this->m_params['placeHolderId'])) {
			
			$this->parentControl = $this->m_params['placeHolderId'];
			
			self::debugOutput('parentControl via placeHolderId: ' . $this->parentControl . '<br />');
			
		}

		$init_array = array();
		
		if (!$this->m_processInternal && isset($this->m_innerCode)) {
			
			$this->m_params['innerCode'] = $this->m_innerCode;
			
			self::debugOutput('!$this->m_processInternal && isset($this->m_innerCode))<br />');
			
		}
		/**
		 * Setup initialization variables. 
		 */
		self::debugOutput('<div style="margin:10px 0;padding:10px;border:1px solid #999;color:#999;">Building init array:<br />');
		foreach($this->m_params as $aname=>$aval) {
			
			$init_array[] = "'" . $aname . "' => '" . str_replace("'", "\'", $aval) . "'";
			
			self::debugOutput('<div style="padding-left:30px;">' . $aname . ' => <span style="color:#009900;">' . htmlentities($aval) . '</span></div>');
			
		}
		self::debugOutput('Done building init array<br /></div>');
		
		$init_params = 'array('.implode(',', $init_array) . ')';
		self::debugOutput('$init_params = ' . $init_params . '<br />');
		
		// Be sure not to overwrite any existing initialization values
		if (isset($this->init_code)) {
			
			$init_code .= $this->init_code;
			
			self::debugOutput('isset($this->init_code)<br />');
			
		}
		
		$init_code .= 'if (!class_exists(\'' . $this->objectType . '\')) FrameworkManager::loadControl(\'' . $this->objectType . '\');'."\r\n";
		if ($build_for_page) {
			$init_code .= 'Page::addControl(\'' . $this_id . '\', new ' . $this->objectType . '(' . $init_params . '));'."\r\n";
		} else {
			$init_code .= '$' . $this_id .' = new ' . $this->objectType . '('.$init_params.');'."\r\n";
		}
		
		self::debugOutput('<div style="border:1px solid #ccc;margin:10px 0;padding:10px;background-color:#ddffdd;">Init code: <br />' . nl2br(htmlentities($init_code)) . '</div>');
		
		$this->init_code = $init_code;
		
		/**
		 * Setup variable attachment/assignment, i.e. $parentControl->addControl($this_control)
		 */
		 
		// Be sure not to overwrite any existing attachment values
		if (isset($this->attach_init_code)) {
			
			$attach_init_code = $this->attach_init_code;
			
			self::debugOutput('isset($this->attach_init_code)<br />');
			
		}

		if (isset($this->parentControl)) {
			
			if ($build_for_page) {
				/**
				 * Old version 
				 **/
				$attach_init_code .= '
					if ($parent_control = Page::getControlById(\'' . $this->parentControl . '\')) {
						if ($child_control = Page::getControlById(\'' . $this_id . '\')) {
							$parent_control->addControl($child_control);
						}
					}';
				/**
				 * New version:
				 **/
				 #$attach_code = array('parentControlName', 'childControlName');
				
			} else {
				$attach_init_code .= 'if (isset($' . $this->parentControl . ')) $' . $this->parentControl .'->addControl($'. $this_id . ');'."\r\n";
			}
			
			self::debugOutput('isset($this->parentControl)<br />');
			
		}
		
		self::debugOutput('<div style="border:1px solid #ccc;margin:10px 0;padding:10px;background-color:#ddddff;">Attach init code: <br />' . nl2br(htmlentities($attach_init_code)) . '</div>');
		
		$this->attach_init_code = $attach_init_code;
		
	}
	
	public function getPassThrus() { return $this->passThru; }
	public function addPassThru($pass_thru) {
		if (!in_array($pass_thru, $this->passThru)) {
			$this->passThru[] = $pass_thru;
		}
	}
	public function removePassThru($remove_pass_thru) {
		$pass_thrus = $this->getPassThrus();
		$this->resetPassThrus();
		foreach($pass_thrus as $pass_thru) {
			if ($pass_thru != $remove_pass_thru) $this->addPassThru($pass_thru);
		}
	}
	public function resetPassThrus() { $this->passThru = array(); }
	public function addPassThrus($pass_thrus) {
		foreach($pass_thrus as $pass_thru) {
			$this->addPassThru($pass_thru);
		}
	}
	
	protected function getLocalDir() {
		static $local_dir = null;
		if (is_null($local_dir)) {
			$r = new ReflectionClass($this);
			$local_dir = dirname($r->getFileName()) . DIRECTORY_SEPARATOR;
		}
		return $local_dir;
	}
	
	public static function ParseConfigString($config_string) { // Should be called statically
		$config = array();
		$lines = explode("\n", $config_string);
	
		foreach($lines as $line) {
			if (!empty($line)) {
				list($name, $value) = explode('=', $line, 2);
				$config[trim($name)] = trim($value);
			}
		}
		return $config;
	}	
	public static function BuildConfigString($config_array) {
		$config_params = array();
		foreach($config_array as $config_name=>$config_value) {
			$config_params[] = $config_name . '=' . $config_value;
		}
		return implode("\r\n", $config_params);
	}
	
}

function _sortControlCollection(Control $control_a, Control $control_b) {
	$sort_a = $control_a->getParam('order');
	$sort_b = $control_b->getParam('order');
	if (empty($sort_a)) $sort_a = 0;
	if (empty($sort_b)) $sort_b = 0;

	return (($sort_a<$sort_b) ? -1 : 1);
}
class ControlCollection extends Collection {
	public function add($control) {
		if (!$control->getParams()->isDefined('order')) $control->setParam('order', $this->getCount()+1);
		parent::add($control);
	}
	/**
	 * Sort items in the collection by their "order" parameter
	 **/
	public function sort() {
		$array = &$this->getAll();
		usort($array, '_sortControlCollection');
	}
}
/** 
 * Manages a collection of controls, allowing new controls to be built from files or text.  Also acts as a threat that permeates all controls that are loaded via a ControlManager instances
 **/
class ControlManagerFactory implements IFactory {
	public function createService(IServiceManager $serviceManager) {
		return new ControlManager();
	}
}
class ControlManager implements IServiceManagerAware {
	private $compiledResults;
	private $controls, $controlAddedEventListeners;
	private $controlCount=0; // Keep track of # of controls being added
	private $initializationStarted = false;
	#private $inits;
	private $attachments;
	private $params;
	private $log;
	private $serviceManager;

	public function getServiceManager() { return $this->serviceManager; }
	public function setServiceManager(IServiceManager $service_manager) {
		$this->serviceManager = $service_manager;
	}
	/**
	 * @param $locked Keep track of whether the controls have already been rendered
	 **/
	private $locked = false;
	function __construct() {
		$this->compiledResults = new CompileControlResult();
		$this->controls = new Dictionary();
		$this->attachments = new Dictionary();
		$this->controlAddedEventListeners = new Dictionary();
		$this->params = new Dictionary();
		$this->log = new Collection();
		#$this->inits = new Collection();
	}
	
	private function log($entry) {
		$this->log->add($entry);
	}
	public function getLog() { return $this->log; }
	
	public function loadControlsFromText($text) {
		$this->log('loadControlsFromText');
		#echo 'ControlManager::loadControlsFromText('.strlen($text) . ')<br />';
		// Get current compile mode so that it can be restored
		$restore = CM::get('CONTROL_COMPILE_MODE');
		// Change compile mode so that it returns an CompileControlResult object
		CM::set('CONTROL_COMPILE_MODE', 'CompileControlResult');
		$result = CompileControl::compile($text);
		#echo '<pre>loadControlsFromText Result: ';print_r($result);exit;
		// Merge the results with the current set
		$this->compiledResults->merge($result);
		$this->params->mergeDictionary($result->getParams());
		// Restore compile mode
		CM::set('CONTROL_COMPILE_MODE', $restore);
	}
	
	public function loadControlsFromFile($file) {
		
		$this->log('loadControlsFromFile: ' . $file);
		
		if ($file_path = PathManager::translate($file)) {
			
			$this->log('loadControlsFromFile: translated: ' . $file_path);
			
			return $this->loadControlsFromText(file_get_contents($file_path));
			
		} else {
			
			$this->log('loadControlsFromFile: unable to loadControlsFromFile(' . $file . ')');
			
			$d = new Dictionary();
			$d->set('file', $file);
			Custodian::log('ControlManager', 'Unable to loadControlsFromFile(${file})', $d);
			
		}
		
	}
	
	/**
	 * Handles addition of new controls
	 * @access public however, it should be treated as if it were a private method since it will only get called as an result of an event occuring
	 **/
	public function _handledControlAdded(CWI_EVENT_Event $event, CWI_EVENT_ControlAddedArgs $args) {
		
		$this->log('_handledControlAdded');
		
		$parent = $event->getSender(); // Parent
		$child = $args->getAddedControl(); // Child
		
		// Remove attachment reference since the child object has already been hard-added to the parent object
		$this->attachments->del($child->getId());
		
		/*
		$child_sortorder
		*/
		/*
		 Below would be the better way to go if we can remove the event handlers
		 
		if ($listener = $this->controlAddedEventListeners->get($args->getAddedControl()->getId())) {
			echo 'Yes: ' . $args->getAddedControl()->getId() . '<br />';
			if ($control = $this->getControls()->get($args->getAddedControl()->getId())) {
				CWI_EVENT_Manager::removeListener($control, $listener);
			}
		} else echo 'No: ' . $args->getAddedControl()->getId() . '<br />';
		**/
	}
	
	/**
	 * Initialize controls
	 **/
	public function initialize() {
		
		/**
		 * Initiate event for controlsInitiating, but only the first time this method is called
		 **/
		if (!$this->initializationStarted) {
			
			$this->log('initialize() first time');
			
			CWI_EVENT_Manager::trigger($this, 'controlsInitializing');
			
			#$this->inits->merge($this->compiledResults->getInitializations());
			#echo '<pre>';print_r($this->compiledResults->getInitializations());exit;
			
			
		}
		
		// Create local version of required attachments (so that they can be manipulated independently of the original attachment objects) <!--- NOT SURE IF THIS IS TRUE ANY MORE
		if (is_null($this->attachments)) $this->attachments = new Dictionary();
		
		$compiled_attachments = $this->compiledResults->getAttachments()->getAll();
		while ($attachment_field = $compiled_attachments->getNext()) {
			
			// Make sure attachment has not already been initialized
			if (!$attachment_field->getDefinition()->isInitialized()) {
				// Set local copy of attachment
				$this->attachments->set($attachment_field->getKey(), $attachment_field->getDefinition());
				// Mark attachment as initialized
				$attachment_field->getDefinition()->isInitialized(true);
				
			}
			
		}
		
		$this->log('initialize()');
		$this->initializationStarted = true;
		
		// Auto load additional control files
		if ($auto_load_control_files = $this->compiledResults->getParams()->get('auto_load_control_files')) {
			// Remove auto_load_control_files so that they do not get stuck in an infinite loop
			$this->compiledResults->getParams()->del('auto_load_control_files');
			
			while ($file = $auto_load_control_files->getNext()) {
				
				$this->log('Auto Load Control File: ' . $file);
				
				$this->loadControlsFromFile($file);
				
			}
			
		}
		
		if ($auto_load_template_ids = $this->compiledResults->getParams()->get('auto_load_template_ids')) {
			
			// Remove auto_load_template_ids so that they do not get stuck in an infinite loop
			$this->compiledResults->getParams()->del('auto_load_template_ids');
			
			FrameworkManager::loadManager('theme');
			
			while ($template_id = $auto_load_template_ids->getNext()) {
				
				$this->log('Auto Load Template ID: ' . $template_id);
				
				if ($template = CWI_MANAGER_ThemeManager::getTemplate(Page::getTheme(), $template_id)) {
					
					$this->log('Auto Load Template ID: ' . $template_id . ': Found');
					
					$this->loadControlsFromFile($template->getTemplateFile());
					
					$template_stylesheets = $template->getStylesheets();
					
					while ($stylesheet = $template_stylesheets->getNext()) {
						Page::addStylesheet($stylesheet);
					}
					
				} else {
					$this->log('Auto Load Template ID: ' . $template_id . ': Not Found');
				}
				
			}
			
		}
		// Iterate through initializations
		while ($init = $this->compiledResults->getInitializations()->getNext()) {

			$instance_name = $init->getInstanceName();
			$class_name = $init->getControlClassName();
			$this->log('Initialize: Class: ' . $class_name . '; Instance Name: ' . $instance_name . '; Already initialized: ' . ($init->isInitialized() ? 'Yes':'No'));
			
			// If the init has not already been initialized then initialize it now
			if (!$init->isInitialized()) {
				
				if (!class_exists($class_name)) {

					$class_key = strtolower($class_name);
					if (substr($class_key, -7) == 'control') $class_key = substr($class_key, 0, -7);
					FrameworkManager::loadControl($class_key);
					
				}
				$params = $init->getParams();
				
				// Add value to params to keep track of the order in which controls were added
				#$params->set('controlManagerControlCount', $this->controlCount);
				
				// Initialize object
				$obj = new $class_name($params);
				#echo 'Initializing: ' . $class_name . ' (' . $params->get('id') . ', ' . $params->get('order') . ')<br />';
				// Listen for any time this control has controls manually added to it so that we can update the ControlManager structure accordingly
				#$this->controlAddedEventListener = 
				$this->controlAddedEventListeners->set($obj->getId(), CWI_EVENT_Manager::listenFor($obj, 'controlAdded', array($this, '_handledControlAdded')));
				$this->log('this->controls->set('.  $instance_name . ')');
				// Add instance of control
				$this->controls->set($instance_name, $obj);
				
				// Mark control as initialized
				$init->isInitialized(true);
				$this->controlCount ++;
				
			}
		}
		
	}
	
	public function getControls() { 
		return $this->controls;
	}
	
	private function attach() {
		#echo 'Attachments<br />';

		// Grab all attachment specifications
		$attachments = $this->attachments->getAll();

		// Iterate through each...
		while ($attachment = $attachments->getNext()) {
			// Get the CompileControlAttachment object associated with the attachment
			$attach = $attachment->getDefinition();
			// Child object
			$child = $this->getControls()->get($attach->getChildName());
			
			$this->log('<span style="background-color:#ff0;">Attach ' . $attach->getChildName() . ' to '. $attach->getParentName() . '; Parent Exists: ' . (($this->getControls()->get($attach->getParentName()) !== false) ? 'Yes':'No') . '</span>');
			
			if ($parent = $this->getControls()->get($attach->getParentName())) {
				
				$this->log('Successfully added to parent');
				$parent->addControl($child);
				
			} else {
				
				$d = new ConfigDictionary();
				$d->set('parent_name', $attach->getParentName());
				$d->set('child_name', $attach->getChildName());
				
				Custodian::log('ControlManager', 'Unable to attach control ${child_name} to ${parent_name}', $d);
				#echo 'Could not attach: ';
				#echo '<pre>';
				#print_r($attachment);
				#exit;
			}
		}

	}
	
	/**
	 * Render the results
	 **/
	public function render() {

		$this->log('render() => initialize()');
		
		$this->initialize();
		
		CWI_EVENT_Manager::trigger($this, 'controlsInitialized');
		
		$controls = $this->getControls()->getAll();
		
		CWI_EVENT_Manager::trigger($this, 'controlsAttaching');
		
		$this->log('render() => attach()');
		
		$this->attach();
		
		CWI_EVENT_Manager::trigger($this, 'controlsAttached');
		
		$output = '';
		
		$root_controls = new ControlCollection();
		/**
		 * Get the root controls, since all children controls are rendered automatically
		 **/
		while ($control = $controls->getNext()) {
			
			$control_obj = $control->getDefinition();
			
			// Whether the control is at the root of the control structure (i.e. it does not have a parent object
			$is_root_level = ($this->compiledResults->getAttachments()->get($control_obj->getId()) === false);
			
			if ($is_root_level) {
				
				$root_controls->add($control_obj);
				
			}
		}
		
		$root_controls->sort();

		$this->log('render() finalizeContent');
		// Finalize content generation so that "post" controls can properly execute (e.g. ErrorControl / HeaderControl
		while ($control = $root_controls->getNext()) {
			$control->finalizeContent();
		}
		
		$this->log('render() => render controls');
		while ($control = $root_controls->getNext()) {
			$output .= $control->render();
		}
		return $output;
			
	}
	
	public function getParam($name) { return $this->params->get($name); }
	public function getParams() { return $this->params; }
	public function setParam($name, $value) { $this->params->set($name, $value); }
}

class WebControl extends Control {
	
	var $m_templateFile;
	var $m_templateControlObject;
	
	var $template;
	var $dataSource = null;
	
	/**
	 * Configuration variables, used by PageControls to automatically construct/initialize object
	 */
	var $m_config = array();
	var $m_configChanged = false;
	
	/**
	 * If this control is being generated as a PageControl, this will contain the page_control object
	 */
	var $m_isPageControl = 0; // Only set to yes if being generated by PageControlLogic

	public function preRender() {
		$rendered_control_id = $this->getId();

		$content = $this->getRenderedContent();
		$this->setRenderedContent($content);
	}
	
	public function processParams($params) {}
	
	public function getInnerCode() { 
		/**
		 * Transitional code for params - the old version was an associative array.  The new version is a Dictionary
		 **/
		$params = $this->getParams();
		
		if (is_array($params)) { // Old version of params
			if (isset($params['innerCode'])) return $params['innerCode'];
			#else return false;
		} else if (is_object($params) && is_a($params, 'Dictionary')) { // New version of params
			return $params->get('innerCode');
		} 
		// Last resort for older implementations of innerCode at $this->m_innerCode
		if (isset($this->m_innerCode)) return $this->m_innerCode;
		// If we get to here then this is an unknown case
		return false;
		#return $this->m_innerCode;
	}
	public function setInnerCode($content) { 
		/**
		 * Transitional code for params - the old version was an associative array.  The new version is a Dictionary
		 **/
		$params = $this->getParams();
		
		if (is_array($params)) { // Old version of params
			$params['innerCode'] = $content;
		} else if (is_object($params) && is_a($params, 'Dictionary')) { // New version of params
			$params->set('innerCode', $content);
		} 
		
		#$this->m_innerCode = $content;
	}
	
	public function getFileKey() { // used to generate path information for $this control
		$class_name = get_class($this);
		$file_key = strtolower(str_replace('control', '', $class_name));
		return $file_key;
	}
	public function getConfigFilePath() {
		return '~/controls/' . $this->getFileKey() . '/config.xml';
	}
	
	public function setConfigValue($name, $value) {
		$this->m_configChanged = true;
		$this->m_config[$name] = $value;
	}
	
	public function getConfigValue($name) { if (isset($this->m_config[$name])) return $this->m_config[$name]; else return false; }
	public function configChanged() { return $this->m_configChanged; }
	
}

class DataWebControl extends WebControl {
	var $m_data;
	var $m_dataSource = '';
	function __construct($init=array()) {
		parent::__construct($init);
		$this->setData(new Collection());
		/*
		if (strlen($this->getDataSource()) > 0) {
			$this->bindData();
		}
		*/
	}
	
	public function getDataSource() { return $this->m_dataSource; } 
	public function setDataSource($str_source) {
		$this->m_dataSource = $str_source;
	}
	public function getData() {
		return $this->m_data;
	}
	public function setData($ilist_object) { 
		$this->m_data = $ilist_object;
		$this->m_data->resetIndex(); // In case of reuse, make sure index is reset
	}
	
	protected function bindData() {
		list($class_name, $method_name) = explode('::', $this->getDataSource());
		$logic = strtolower(str_replace('Logic', '', $class_name));
		FrameworkManager::loadLogic($logic);
		$data = eval('return ' . $this->getDataSource() . ';');

		if (isset($data)) {
			/*if (!is_a('List', $data)) { // This might happen if an IList is passed directly rather than an array of IList-typed objects
				$tmp = new Collect();
				$tmp->add($data);
				$data = $tmp;
			}*/
			$this->setData($data);
		}
	}
	public function render() {
		//$test = $this->getData();

		if (strlen($this->getDataSource()) > 0) {
			$this->bindData();
		}
		return WebControl::render();
	}
}

class ControlTemplateHelper {
	public static function parseTemplate($display_format) {
		/*$template = array(
			'display_format' => '',
			'replacements' => array()
			);
		*/	
		
		preg_match_all('|<Data.*?/>|', $display_format, $find_fields);

		if (isset($find_fields[0])) {
			
			$find_fields = $find_fields[0];
			$replacements = array();

			$data_tag_template = new _DataTagTemplate($display_format);

			foreach($find_fields as $field) {

				$xml_obj = CWI_XML_Tag::parseTag($field);
					
				$data_tag_template->addField($field, $xml_obj);
				
			}
			
			return $data_tag_template;

		}
		
		return false;
	}
}
class _DataTagTemplate {
	var $_template;
	var $_values = array();
	var $_fields = array();
	function __construct($template) {
		$this->_template = $template;
	}
	public function set($name, $value=null) {
		if (is_array($name) || is_object($name)) { // Special Processing
			if (is_object($name)) $name = get_object_vars($name);
			
			foreach ($name as $var_name=>$var_value) {
				$this->_values[$var_name] = $var_value;
			}
		} else {
			$this->_values[$name] = $value;
		}
	}
	public function getValue($name) {
		if (isset($this->_values[$name])) {
			return $this->_values[$name];
		} else {
			return false;
		}
	}
	public function resetValues() {
		$this->_values = array();
	}
	public function render() {
		$render_output = $this->_template;
		foreach($this->_fields as $field_name => $field) {
			
			$replacements = $field->getReplacements();
			
			if (count($replacements) > 0) {
				
				$new_value = $this->getValue($field_name);
				
				foreach($replacements as $replacement) {
					
					$replace_text = $replacement->getReplaceText();
					$xml_obj = $replacement->getXmlObj();
					
					if (empty($new_value) && $xml_obj->getParam('default')) $new_value = $xml_obj->getParam('default');
					
					if ($format = $xml_obj->getParam('format')) {
						if (strpos($format, '%field') !== false) {
							$formatted = str_replace('%field', $new_value, $format);
							$new_value = eval('return ' . $formatted . ';');
						}
					}
					$render_output = str_replace($replace_text, $new_value, $render_output);
				}
			}
			
		}
		return $render_output;
	}
	public function addField($tag_text, $xml_obj) {
		if ($get_field = $xml_obj->getParam('field')) {
			if (!isset($this->_fields[$get_field])) {
				$this->_fields[$get_field] = new _DataTagTemplateField($get_field);
			}
			$this->_fields[$get_field]->addReplacement($tag_text, $xml_obj);
		}
	}
	public function getTemplate() { return $this->_template; }
	public function setTemplate($template) { $this->_template = $template; }
	
}

class _DataTagTemplateField {
	var $_fieldName, $_replacements = array();
	function __construct($field_name) {
		$this->_fieldName = $field_name;
	}
	public function addReplacement($tag_text, $xml_obj) {
		array_push($this->_replacements, new _DataTagTemplateReplacement($tag_text, $xml_obj));
	}
	public function getReplacements() { return $this->_replacements; }
	public function getFieldName() { return $this->_fieldName; }
}
class _DataTagTemplateReplacement {
	var $_replaceText, $_xmlObj;
	public function _DataTagTemplateReplacement($replace_text, $xml_obj) {
		$this->_replaceText = $replace_text;
		$this->_xmlObj = $xml_obj;
	}
	public function getReplaceText() { return $this->_replaceText; }
	public function getXmlObj() { return $this->_xmlObj; }
}
?>