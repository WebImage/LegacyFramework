<?php

/**
 * CHANGELOG
 * 10/22/2009	(Robert Jones) Replaced the entire XmlCompile class to be more efficient (still need to add check for valid closing tag [moved to xml/compiler.php]
 * 12/01/2009	(Robert Jones) Renamed XmlCompile to CWI_XML_Compile [moved to xml/compiler.php]
 * 01/27/2009	(Robert Jones) Added throw/catch to CWI_XML_Compile to allow it to more gracefully handle various XML compile situations [moved to xml/compiler.php]
 * 02/23/2010	(Robert Jones) Fixed bug where CWI_XML_Compile would fail when a comment contained XML code [moved to xml/compiler.php]
 * 05/08/2010	(Robert Jones) Added option for templateId to CompileControl::compile() for inclusion in the <@Page .../> tag in controls.  This is primarily used for theming/templates so that an ID can be specified related to a theme, rather than a specific file
 * 05/11/2010	(Robert Jones) Moved most XML based classes to their own files within the libraries/xml/ directory
 */

#FrameworkManager::loadLibrary('controls'); 
#FrameworkManager::loadLibrary('xml.xml');
FrameworkManager::loadControl('literal');
#FrameworkManager::loadLibrary('xml.compile');
#FrameworkManager::loadLibrary('xml.xslt');

/**
 * Convenience class to keep track of variables that will be initiated by the rendering process 
 **/
interface ICompileControlComponent {
	/**
	 * @return bool whether the component is initialized
	 **/
	public function isInitialized();
}
class AbstractCompileControlComponent implements ICompileControlComponent {
	private $componentInitialized = false;
	/**
	 * Getter/setter
	 **/
	public function isInitialized($true_false=null) {
		
		if (is_null($true_false)) { // Getter
			return $this->componentInitialized;
		} else {
			if (!is_bool($true_false)) throw new Exception('Invalid parameter passed.  Boolean required.');
			$this->componentInitialized = $true_false;
		}
		
	}
}
class CompileControlInit extends AbstractCompileControlComponent {
	private $controlClassName, $instanceName, $params;
	/**
	 * Whether this is a root level of control and should be rendered
	 **/
	#private $isRootLevel = false;
	function __construct($control_class_name, $instance_name, ControlConfigDictionary $params) {
		$this->controlClassName = $control_class_name;
		$this->instanceName = $instance_name;
		$this->params = $params;
	}
	public function getControlClassName() { return $this->controlClassName; }
	public function getInstanceName() { return $this->instanceName; }
	public function getParams() { return $this->params; }
	
}
class CompileControlAttachment extends AbstractCompileControlComponent {
	private $childName, $parentName;
	function __construct($child_name, $parent_name) {
		$this->childName = $child_name;
		$this->parentName = $parent_name;
	}
	public function getChildName() { return $this->childName; }
	public function getParentName() { return $this->parentName; }
}
class CompileControlAutoLoadControlFile extends AbstractCompileControlComponent {
	private $file;
	function __construct($file) {
		$this->file = $file;
	}
}
#$init[] = [ClassNameControl], [InstanceName], $params
#$attach[] = [ChildInstanceName], [ParentInstanceName]
#$render = [InstanceName];
/**
 * An object to hold the various results associated with a compiled control
 **/
class CompileControlResult {
	private $inits, $attachs;
	private $params;//ControlConfigDictionary
	/**
	 * Keep track of parent pools and their current maximum sort order so that an "order" value can be 
	 **/
	private $childOrder;
	function __construct() {
		
		// Instantiate properties
		$this->inits = new Collection(); // Collection instead of dictionary because the control name could change in code somewhere and the key value would have no way of updating
		$this->attachs = new Dictionary();
		$this->params = new ControlConfigDictionary();
		$this->parentChildOrder = new Dictionary();
	}
	public function addAutoLoadControlFile($file) {
		if (!$auto_load_control_files = $this->getParams()->get('auto_load_control_files')) {
			$auto_load_control_files = new Collection();
			$this->getParams()->set('auto_load_control_files', $auto_load_control_files);
		}
		$auto_load_control_files->add($file);
	}
	public function addAutoLoadTemplate($template_id) {
		if (!$auto_load_template_ids = $this->getParams()->get('auto_load_template_ids')) {
			$auto_load_template_ids = new Collection();
			$this->getParams()->set('auto_load_template_ids', $auto_load_template_ids);
		}
		$auto_load_template_ids->add($template_id);
	}
	/**
	 * Adds a set of values that will be used to initialize a class
	 * @param CompileControlInit An object holding the requisite initialization values (control class name, instance name, parameters)
	 **/
	public function addInitialization(CompileControlInit $init) {
		$this->inits->add($init);
	}
	public function createInitialization($control_class_name, $instance_name, ControlConfigDictionary $params, $parent_name='') {
		$init = new CompileControlInit($control_class_name, $instance_name, $params);
		$this->addInitialization($init);//$this->inits->add($init);
		
		if (empty($parent_name)) {
			$this->generateControlOrder('__ROOT__', $instance_name);
		} else {
			$this->createAttachment($instance_name, $parent_name);
		}
		return $init;
	}
	private function getInitByName($n) {
		$inits = $this->getInitializations();
		while ($init = $inits->getNext()) {
			if ($init->getInstanceName() == $n) {
				$inits->resetIndex(); // Return index to proper state
				return $init;
			}
		}
	}
	/**
	 * Based on the passed $parent_name, find the next sort order for an added child
	 **/
	private function generateControlOrder($parent_name, $child_name) {
		
		// Find the init that the child belongs to...
		if ($init = $this->getInitByName($child_name)) {
			
			/**
			 * Check if it already has an "order" parameter attached to it, otherwise create one here
			 **/
			$param_order = $init->getParams()->get('order');
			
			if (empty($param_order)) {
				/**
				 * Current current max sort order
				 **/
				if (!$max_order = $this->parentChildOrder->get($parent_name)) {
					$max_order = 0;
				}
				$max_order ++;
				// Update param sort order
				$init->getParams()->set('order', $max_order);
				// Store new max order
				$this->parentChildOrder->set($parent_name, $max_order);
				
			}
			
		} else {
			$d = new Dictionary();
			$d->set('parent_name', $parent_name);
			$d->set('child_name', $child_name);
			Custodian::log('CompileControl', 'generateControlOrder(${parent_name}, ${child_name}) unable to find child control', $d, CUSTODIAN_WARNING, __FILE__.':'.__LINE__);
		}
	}
	/**
	 * Sets up a relationship between what child should be attached to what parent
	 **/
	public function addAttachment(CompileControlAttachment $attachment) {
		$this->attachs->set($attachment->getChildName(), $attachment);
		// Generate next sort order if one is not defined for the child control (based on the parent control's current count)
		#echo 'addAttachment: ' . $attachment->getChildName() . ' to ' . $parent_name . '<br />';
		$this->generateControlOrder($attachment->getParentName(), $attachment->getChildName());
	}
	public function createAttachment($child_instance_name, $parent_instance_name) {
		if (empty($child_instance_name)) throw new Exception('$child_instance_name cannot be empty');
		if (empty($parent_instance_name)) throw new Exception('$parent_instance_name cannot be empty');
		
		$attachment = new CompileControlAttachment($child_instance_name, $parent_instance_name);
		$this->addAttachment($attachment);
		return $attachment;
	}
	/**
	public function addRender($render) {
	}
	**/
	/**
	 * Get the required initializations
	 * @return Collection
	 **/
	public function getInitializations() { return $this->inits; }
	/**
	 * Get the required attachments
	 * @return Dictionary
	 **/
	public function getAttachments() { return $this->attachs; }
	/** 
	 * Get the parent id of an object (by id) 
	 **/
	public function getParentId($object_id) {
		return $this->attachs->get($object_id);
	}
	/**
	 * Merge another compiled result set into this class
	 **/
	public function merge(CompileControlResult $result) {
		
		$this->getInitializations()->merge($result->getInitializations());
		$this->getAttachments()->mergeDictionary($result->getAttachments());
		$this->params->mergeDictionary($result->getParams());
		
	}
	
	public function getParam($name) { return $this->params->get($name); }
	public function getParams() { return $this->params; }
	public function setParam($name, $value) { $this->params->set($name, $value); }
}

class CompileControl {
	var $count = 1;
	
	public static function &getGenericControlId() {
		static $count;
		if (!isset($count[0])) {
			$count = array();
			$count[0] = 0;
		}
		$count[0]++;
		return $count[0];
	}
	
	private static function _loopCompile($text, $options=array(), $start, $object, $result, &$current_position=0, $prepend_ids='') {
		static $times_called;
		if (!$times_called) $times_called = 0;
		$times_called ++;
		
		$new_object = CompileControl::_compile($text, $options, $start, $object, $result, $current_position, $prepend_ids);
		return $new_object;
	}
	
	// Shortcut for calling compile() with prepended IDs
	public static function compilePrepend($text, $prepend_ids) {
		return CompileControl::_compile($text, array(), 0, null, $prepend_ids);
	}
	
	public static function compile($text) {
		return CompileControl::_compile($text);
	}
	/**
	 * OPTIONS:
	 * 	build_for_page = true|false     Used for getInitCode
	 */
	private static function _compile($text, $options=array(), $start=0, $object=null, $result=null, &$current_position=0, $prepend_ids='') {
		
		$params = null; // Needed for tag parameters
		#echo 'CompileControl::_compile(strlen($text)=' . strlen($text) . ', $start: ' . $start . ', $current_position: ' . $current_position . ')<br />';
#
# Figure out how to remove:
# $object
# $this_object 
# $this_object->m_processInternal
#
		$CONTROL_COMPILE_MODE = ConfigurationManager::get('CONTROL_COMPILE_MODE'); // CompileControlResult | WebControl
		
		if (!isset($options['build_for_page'])) $options['build_for_page'] = true;
/*		
static $str_len_count;
if (is_null($str_len_count)) $str_len_count = 0;
$str_len_count ++;
echo 'Strlen: ' . $str_len_count . '<br />';
*/
		
		$text_length = strlen($text);
		
		// Keep track if this is the root request so that we can display debuging information for the final result
		$is_root_request = false;
		
		if (is_null($result)) {
			
			$result = new CompileControlResult();
			#if ($CONTROL_COMPILE_MODE == 'CompileControlResult') 
			$is_root_request = true;
		}
		
		#
		# BEGIN REMOVE
		#
		if (is_null($object)) {
			
			$object = new WebControl();
			$object->init_code = '';
			$object->attach_init_code = '';
			$object->render_code = '';
			#$object->tagStack = array();
			
		}
		#
		# END REMOVE
		#
		
		$text_buffer = '';
		$code_buffer = '';

		for($i=$start; $i < $text_length; $i++) {
			$is_text = true;
		
			#$object->pos = $i;
			$current_position = $i;
			
			if ($text[$i] == '<') {
				
				$is_close_tag = ($text[$i+1] == '/');
				
				$outer_tag_start = $i;
				$outer_tag_length = strpos($text, '>', $i) - $outer_tag_start + 1;
				$outer_tag = substr($text, $outer_tag_start, $outer_tag_length);
				
				$xml_tag = CWI_XML_Tag::parseTag($outer_tag);
				
				if (strlen($xml_tag->getNamespace()) == 0) {
					
					if (substr($outer_tag, 0, 6) == '<@Page') {
						$i += $outer_tag_length - 1;
						#
						# MOVE SOMEWHERE?
						#
						
						$object->master_page_file = $xml_tag->getParam('masterPageFile');
						$object->template_id = $xml_tag->getParam('templateId');
						#
						# END MOVE SOMEWHERE?
						#
						
						#
						# MOVE SOMEWHERE?
						#
						if ($page_title = $xml_tag->getParam('title')) Page::setTitle($page_title);
						if ($meta_tag_description = $xml_tag->getParam('metaTagDescription')) Page::setMetaTag('description', $meta_tag_description);
						#
						# END MOVE SOMEWHERE?
						#
						
						#
						# BEGIN ADD
						#
						#if ($master_page_file = $xml_tag->getParam('masterPageFile')) $result->setParam('masterPageFile', $master_page_file);
						if ($master_page_file = $xml_tag->getParam('masterPageFile')) {
							
							$result->addAutoLoadControlFile($master_page_file);
							
							/*
							$get_id = &CompileControl::getGenericControlId();
							$include_control_name = 'tc1_' . $get_id; // tc = text control
							$include_control_name = uniqid($include_control_name);
							
							$params = new ControlConfigDictionary(array('file'=>$master_page_file, 'id'=>$include_control_name));
							$result->createInitialization('IncludeControl', $include_control_name, $params);
							*/
						}
						#if ($template_id = $xml_tag->getParam('templateId')) $result->setParam('templateId', $template_id);
						if ($template_id = $xml_tag->getParam('templateId')) {
							$result->addAutoLoadTemplate($template_id);
						}
						
						if ($page_title = $xml_tag->getParam('title')) $result->setParam('pageTitle', $page_title);
						if ($meta_tag_description = $xml_tag->getParam('metaTagDescription')) $result->setParam('pageMetaDescription', $meta_tag_description);						
						#
						# END ADD
						#
						
						continue;
					}
					
				} else { //
					// Process any text in the buffer and add it to the structure
					#
					# FIGURE OUT HOW TO REMOVE ->hold_for_tag
					#
					
					
					if (!empty($text_buffer) && !isset($object->hold_for_tag)) {
						
						$get_id = &CompileControl::getGenericControlId();
						$text_control_name = 'tc1_' . $get_id; // tc = text control
						$text_control_name = uniqid($text_control_name);
						
						$params = new ControlConfigDictionary(array('text'=>$text_buffer, 'id'=>$text_control_name));
						
						#
						# BEGIN ADD
						#
						
						// Add attachment code for parent hierarchy
						$result->createInitialization('LiteralControl', $text_control_name, $params, $object->getId());
						
						# Figure out how to remove $object
						#if (strlen($object->getId()) > 0) {
						#	$result->createAttachment($text_control_name, $object->getId());
						#}
						
						#
						# END ADD
						#
						
						# 
						# BEGIN REMOVE
						#
						$this_object = new LiteralControl();
						$this_object->objectType = 'LiteralControl';
						$this_object->setParams(array('text'=>$text_buffer, 'id'=>$text_control_name));
						
						$this_object->setId($text_control_name);
						if (strlen($object->getId()) > 0) {
							$this_object->parentControl = $object->getId();
						}
						$this_object->getInitCode($options['build_for_page']);

						$object_init_code = '';
						if (isset($object->init_code)) $object_init_code .= $object->init_code;
						if (isset($this_object->init_code)) $object_init_code .= $this_object->init_code;
						$object->init_code = $object_init_code;
						$attach_init_code = '';
						if (isset($object->attach_init_code)) $attach_init_code .= $object->attach_init_code;
						if (isset($this_object->attach_init_code)) $attach_init_code .= $this_object->attach_init_code;
						$object->attach_init_code = $attach_init_code;
						
						#
						# END REMOVE
						#
						
						$text_buffer = '';
						
						#
						# BEGIN REMOVE
						#
						unset($this_object->init_code);
						unset($this_object->attach_init_code);
						
						$object->addControl($this_object);
						#
						# END REMOVE
						#
					}
					
					// Begin Processing Current Tag
	
					$is_text = false;
					
					$i += $outer_tag_length;
					
					if ( ($xml_tag->getType() == 'Open' || $xml_tag->getType() == 'OpenClose') ) {

						$class_name = $xml_tag->getName() . 'Control';

						if (!class_exists($class_name)) FrameworkManager::loadControl(strtolower($xml_tag->getName()));
						if (!class_exists($class_name)) {
							FrameworkManager::loadLogic('control');
							if ($struct = ControlLogic::getControlByClassName($class_name)) include_once(PathManager::translate($struct->file_src));
						}
						// Last resort
						if (!class_exists($class_name)) $class_name = 'WebControl';
						
						#
						# BEGIN ADD
						#
						$params = new ControlConfigDictionary($xml_tag->getParams());
						
						#
						# END ADD
						#
						
						# BEGIN REMOVE
						#
						// Setup this child and get all of its grandchildren
						$this_object = new $class_name();
						$this_object->objectType = $class_name;
						$this_object->setParams($xml_tag->getParams());
						#$this_object->tagStack = array();
						# 
						# END REMOVE
						#
						if (strlen($this_object->getParam('id')) == 0) {
							
							$get_id = &CompileControl::getGenericControlId();
							// gc = generic_control
							$generic_control_name = 'gc_' . $get_id;
							$generic_control_name = uniqid($generic_control_name);
							
							# 
							# BEGIN ADD
							#
							$params->set('id', $generic_control_name);
							# 
							# END ADD
							#
							
							# 
							# BEGIN REMOVE
							#
							$this_object->setParam('id', $generic_control_name);
							# 
							# END REMOVE
							#
						} else {
							// Prepend ID if necessary
							#
							# BEGIN ADD
							#
							$params->set('id', $prepend_ids . $params->get('id'));
							#
							# END ADD
							#
							
							# 
							# BEGIN REMOVE
							#
							$this_object->setParam('id', $prepend_ids . $this_object->getParam('id'));
							#
							# END REMOVE
							#
						}
						
						#
						# BEGIN ADD
						#
						$parent_name_candidates = array($object->getId(), $params->get('placeHolderId'), $params->get('parentId'));
						$parent_name = null;
						

						foreach($parent_name_candidates as $candidate) {
							
							if (!empty($candidate)) {
								
								$parent_name = $candidate;
								break;
								
							}
							
						}
						
						$result->createInitialization($class_name, $params->get('id'), $params, $parent_name);
						#
						# END ADD
						#
						
						#
						# BEGIN REMOVE
						#
						$this_object->setId($this_object->getParam('id'));
						#
						# END REMOVE
						#
						// Set parent id
						if (strlen($object->getId()) > 0) {

							
							# 
							# BEGIN ADD
							#
							# Figure out how to get rid of $object
							#$result->createAttachment($params->get('id'), $object->getId());
							
							# 
							# END ADD
							#
							
							# 
							# BEGIN REMOVE
							#
							$this_object->parentControl = $object->getParam('id');
							# 
							# END REMOVE
							#
						}
						#
						# BEGIN ADD
						#
						// Kept for legacy attachment
						/*
						if ($place_holder_id = $params->get('placeHolderId')) {
						
							$result->createAttachment($params->get('id'), $place_holder_id);
						
						}
						*/
						// New way of attaching (so that any control can be re-attached to any other control
						/*
						if ($parent_id = $params->get('parentId')) {
							
							$result->createAttachment($params->get('id'), $parent_id);
							
						}*/
						#
						# END ADD
						#
						
						#
						# Check how to get rid of m_processInternal
						#
						if ($xml_tag->getType() == 'Open' && $this_object->m_processInternal) {

							$close_tag = $xml_tag;
							$close_tag->setType('Close');
							
							$children = CompileControl::_loopCompile($text, $options, $i, $this_object, $result, $current_position, $prepend_ids);
							
							#
							# Check how to remove m_innerCode
							#
							if (!isset($object->m_innerCode)) $object->m_innerCode = '';
							
							#REMOVING.... NO NEED FOR THIS?
							$object->m_innerCode = $code_buffer . $outer_tag . $object->m_innerCode . $children->m_innerCode . $close_tag->render();
							
							$code_buffer = '';
							
							$children->getInitCode($options['build_for_page']);
							
							#
							# Check how to remove hold_for_tag
							#
							
							#
							# BEGIN ADD
							#
							# Figure out how to remove $object and $children - NO NEED FOR THIS? YES, NEEDED FOR DataListControl, and probably others
							$params->set('innerCode', $code_buffer . $outer_tag . $object->m_innerCode . $children->m_innerCode . $close_tag->render());

							#
							# END ADD
							#
							
							#
							# BEGIN REMOVE
							#
							if (!isset($object->hold_for_tag)) {
								
								$object_init_code = '';
								if (isset($object->init_code)) $object_init_code .= $object->init_code;
								if (isset($children->init_code)) $object_init_code .= $children->init_code;
								$object->init_code = $object_init_code;
								
								$attach_init_code = '';
								if (isset($object->attach_init_code)) $attach_init_code .= $object->attach_init_code;
								if (isset($children->attach_init_code)) $attach_init_code .= $children->attach_init_code;
								$object->attach_init_code = $attach_init_code;
								
								// Remove init code from children, as only the root should maintain this information
								unset($children->init_code);
								unset($children->attach_init_code);
								
								$object->addControl($children);
							}
							#
							# END REMOVE
							#
							
							#if (isset($children->pos)) {
								#$i=$children->pos-1;
								
								$i = $current_position - 1;
								
							#}
						
						#
						# Check how to get rid of m_processInternal
						#
						} else if ($xml_tag->getType() == 'OpenClose' || !$this_object->m_processInternal) {

							if (!$this_object->m_processInternal) { // This is technically an open tag with a close tag, but all content is retreived up front

								/**
								 * Get Close Tag
								 */
								if ($xml_tag->getType() == 'Open') {
									$close_tag		= $xml_tag;
									$close_tag->setType('Close');
									$close_tag_rendered	= $close_tag->render();
									$close_tag_length	= strlen($close_tag_rendered);
									$close_tag_pos		= strpos($text, $close_tag_rendered, $i);
	
									$literal_content = substr($text, $i, $close_tag_pos-$i);
									#
									# Check how to remove
									#
									$this_object->m_innerCode = $literal_content;
									#
									# BEGIN ADD
									#
									$params->set('innerCode', $literal_content);
									#
									# END ADD
									#
									$new_start = $close_tag_pos + $close_tag_length;
	
									$i = $new_start;
								}
							}
							
							#
							# Check how to remove
							#
							$this_object->getInitCode($options['build_for_page']);
							
							$i -= 1;
							
							#
							# BEGIN REMOVE (possibly)
							$object_init_code = '';
							if (isset($object->init_code)) $object_init_code .= $object->init_code;
							if (isset($this_object->init_code)) $object_init_code .= $this_object->init_code;
							$object->init_code = $object_init_code;
							
							$attach_init_code = '';
							if (isset($object->attach_init_code)) $attach_init_code .= $object->attach_init_code;
							if (isset($this_object->attach_init_code)) $attach_init_code .= $this_object->attach_init_code;
							$object->attach_init_code = $attach_init_code;
							
							unset($this_object->init_code);
							unset($this_object->attach_init_code);
							
							$object->addControl($this_object);
							#
							# END REMOVE (possibly)
							#
						}
					} else { // Close Tag
					
						#
						# Check how to remove m_innerCode
						#
						
						#
						# BEGIN REMOVE
						#
						if (!isset($object->m_innerCode)) $object->m_innerCode = '';
						#
						# END REMOVE
						#
						$object->m_innerCode .= $code_buffer;
						#
						# BEGIN ADD
						#
						
						if (null !== $params) $params->set('innerCode', $params->get('innerCode') . $code_buffer);
						
						#
						# END ADD
						#
						#echo 'Code Buffer: ' . strlen($code_buffer) . '<br />';
						$code_buffer = '';
						
						#
						# Check how to remove hold_for_tag
						#
						if (isset($object->hold_for_tag) && $object->hold_for_tag == $xml_tag->getName()) { // If processing text as literal, end literal processing
						
							unset($object->hold_for_tag);
							$text_buffer = '';
						
						#
						# Check how to remove hold_for_tag
						#	
						} else if (isset($object->hold_for_tag)) { // If processing text as literal, comparing tags do not match.  Continue processing as literal
						
							$text_buffer = '';
							
						} else {
							// Not processing as literal
						}
						
						
						#$object->pos = $i;
						$current_position = $i;
						
						#
						# Check how to remove $object
						#
						return $object;
					}
					
				}
			}
			
			if ($is_text) {
				$text_buffer .= $text[$i];
				$code_buffer .= $text[$i];
			}
			
		}

		if (!empty($text_buffer)) {
			
			#die('text buffer: ' . strlen($text_buffer));
			$get_id = &CompileControl::getGenericControlId();
			$text_control_name = 'tc2_' . $get_id; // tc = text control 
			$text_control_name = uniqid($text_control_name);
			
			#
			# BEGIN ADD
			#
			$params = new ControlConfigDictionary(array('text'=>$text_buffer, 'id'=>$text_control_name));
			
			// Add attachment code for parent hierarchy
			$result->createInitialization('LiteralControl', $text_control_name, $params, $object->getParam('id'));
						
			#
			# END ADD
			#
			
			#
			# BEGIN REMOVE
			#
			$this_object = new LiteralControl();
			$this_object->objectType = 'LiteralControl';
			$this_object->setParams(array('text'=>$text_buffer, 'id'=>$text_control_name));
			$this_object->setId($text_control_name);
			#
			# END REMOVE
			#
			
			#
			# BEGIN REMOVE (but make sure to add code to attach parent
			#
			if (!empty($object->getParam('id'))) {
				$this_object->parentControl = $object->getParam('id');
			}
			#
			# END REMOVE
			#
			
			#
			# BEGIN ADD
			#
			#if (isset($object->m_params['id'])) {
			#	$result->createAttachment($params->get('id'), $object->m_params['id']);
			#}
			#
			# END ADD
			#
			
			#
			# REORGANIZE/REMOVE
			#
			$this_object->getInitCode($options['build_for_page']);
			#
			#
			#
			
			#
			# BEGIN REMOVE
			#
			$object_init_code = '';
			if (isset($object->init_code)) $object_init_code .= $object->init_code;
			if (isset($this_object->init_code)) $object_init_code .= $this_object->init_code;
			$object->init_code = $object_init_code;
			
			$attach_init_code = '';
			if (isset($this_object->attach_init_code)) $attach_init_code .= $this_object->attach_init_code;
			if (isset($object->attach_init_code)) $attach_init_code .= $object->attach_init_code;
			$object->attach_init_code = $attach_init_code;
			$text_buffer = '';
			
			unset($this_object->init_code);
			unset($this_object->attach_init_code);
			
			$object->addControl($this_object);
			#
			# END REMOVE
			#
		}


		#
		# BEGIN ADD
		#
		// Add rendering code?
		#
		# END ADD
		#
		
		#
		# BEGIN REMOVE
		#
		$object->render_code = '';

		#foreach($object->getControls() as $child) {
		$child_controls = $object->getControls();
		
		while ($child = $child_controls->getNext()) {
			if ($options['build_for_page']) {
				if ($child->renderOnRoot()) {
					$object->render_code .= 'if ($render_control = Page::getControlById(\'' . $child->getId() . '\')) echo $render_control->render();'."\r\n";
				} else { // This will ensure that even if this object's rendered output is not outputted, i.e. when <cms:Content> is at the root that it's output can still be cached
					// Do nothing
				}
			} else {
				// Do nothing
			}
		}

		#
		# END REMOVE
		#
		if ($is_root_request) {
			
			#if ($CONTROL_COMPILE_MODE == 'CompileControlResult') {
			
				return $result;
				
			#}
			
		}
		
		return $object;
	}
}

?>