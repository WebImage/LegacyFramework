<?php

/**
 * CHANGELOG
 * 07/13/2009	(Robert Jones) Added html_entity_decode to XmlTag::parseTag() parameter values so that HTML can now be passed through parameters via escaped HTML code
 * 07/13/2009	(Robert Jones) Added XmlTraversal::$_children to keep track of order of children - primarily for XSL/T
 * 10/21/2009	(Robert Jones) Added XmlTag::TYPE_OPEN, XmlTag::TYPE_CLOSE, and XmlTag::TYPE_OPENCLOSE, XmlTag::TYPE_INSTRUCTION constants to be used instead of define('XMLTAG_OPEN', 'open'), etc.
 * 10/22/2009	(Robert Jones) Replaced the entire XmlTraversal class to be more efficient (there is still much room for improvement - i.e. setParent() takes up a LOT of memory on large loads
 * 11/27/2009	(Robert Jones) Renamed XmlTraversal to CWI_XML_Traversal, XmlData => CWI_XML_Data, XmlTag => CWI_XML_Tag
 * 01/07/2010	(Robert Jones) Added CWI_XML_Traversal::getStructure() to output an array structure of the XML tree - generally used for debugging
 * 01/25/2010	(Robert Jones) Added CWI_XML_Traversal::__sleep() and CWI_XML_Traversal::__wakeup() to allow object to be woken up without storing the recursive parent property
 * 01/27/2010	(Robert Jones) Changed CWI_XML_Traversal::getPath() to recursively call itself as it  recursively iterates through an XPath statement
 * 01/27/2010	(Robert Jones) Added CWI_XML_Traversal::removeParent(), primarily for the CWI_XML_Compile::compile() function so that the parent object of a temporary object can be deleted
 * 01/27/2010	(Robert Jones) Fixed CWI_XML_Traversal::getRoot(), which for some reason was trying to return (only summarizing here:) $this->getParent()->getParent() instead of $this->getParent()
 * [UNDID]04/24/2010	(Robert Jones) Added htmlentities() to CWI_XML_Data constructor in case XML entities are added
 * 05/14/2010	(Robert Jones) Fixed bug in XML getPath where the function was checking for colon before brackets, so something like ->getPath('tag[@name="d:any"]') would fail because the bug assumed that this was a namespace because of the colon in the attribute check.
 * 09/03/2010	(Robert Jones) Changed CWI_XML_Tag::render() to render attributes with htmlentities; changed CWI_XML_Data::render() to output encoded values
 * 07/14/2011	(Robert Jones) Changed CWI_XML_Tag::parseTag() to trim tag name and namespace after parsing out.  There was an issue parsing a xml feed that had a tag spanning multiple lines
 * 11/22/2011	(Robert Jones) Modified CWI_XML_Traversal constructor to parse out namespace if included in the format "namespace:tag_name" for the $tag_name attribute
 */

interface ICWI_XML_XPATH_Expression {
	function evaluate();
}

class CWI_XML_XPATH_BoolExpr implements ICWI_XML_XPATH_Expression {
	function evaluate() { return true; }
}
class CWI_XML_XPATH_OrExpr extends CWI_XML_XPATH_BoolExpr{}
class CWI_XML_XPATH_AndExpr extends CWI_XML_XPATH_BoolExpr{}

class CWI_XML_XPATH_LocationPath {}

class CWI_XML_XPATH_ExprGroup {}

class CWI_XML_XPATH_Expr {
	private function createGrouping($string) {
		
	}
	function createFromContextAndString($context, $string) {
		/**
		 * VALUE TYPES:
		 * String
		 * Boolean
		 * Number
		 * Node-set
		 * 
		 * Grouping		: ()
		 * Filter		: []
		 * Unary minus		:	-
		 * Multiplication	: *, div, idiv
		 * Addition		: +, -
		 * Relational		: = != < <= > >=
		 * Union		: |
		 * Negation		: not
		 * Conjunction		: and
		 * Disjunction		: or
		 */
	}
}

class CWI_XML_Traversal {
	/**
	 * The name of the XML tag
	 */
	private $tagName;
	/**
	 * The xml tag's namespace
	 */
	private $namespace;
	/**
	 * The XML tag's parameters
	 */
	private $params;
	/**
	 * This tags children (tags or data)
	 */
	private $children = array();
	/**
	 * The parent XML tag, if any
	 */
	private $parent = null;
	
	public $contextPosition;
	
	/*
	var $params = array();
	var $_children = array();
	
	var $_data;
	var $_innerCode;
	*/
	function __construct($tag_name=null, $data=null, $params=array()) {
		
		if (strlen($tag_name) > 0) {
			$colon_pos = strpos($tag_name, ':');
			
			if ($colon_pos > 0) {
				
				$namespace = substr($tag_name, 0, $colon_pos);
				$tag_name = substr($tag_name, $colon_pos+1);
				
				$this->setNamespace($namespace);
				
			}
			
			$this->setTagName($tag_name);
		}
		
		if (strlen($data) > 0) $this->setData($data);
		$this->setParams($params);
	}
	/**
	 * Allow object to be serialized without "parent" which can can be reset once the object is woken up via __wakeup()
	 * @return array the properties that will be serialized
	 */
	function __sleep() {
		return array('tagName', 'namespace', 'params', 'children', 'contextPosition');
	}
	/**
	 * Allow an object to be unserialized.  Re-sets the parent object of all children, which were removed when serialied/__sleep()
	 * @return null
	 */
	function __wakeup() {
		$count = count($this->children);
		for($i=0; $i < $count; $i++) {
			if (is_a($this->children[$i], 'CWI_XML_Traversal')) $this->children[$i]->setParent($this);
		}
	}
	function getTagName() { return $this->tagName; }
	function getFullTagName() {
		$name = '';
		if (strlen($this->getNamespace()) > 0) $name = $this->getNamespace() . ':';
		$name .= $this->getTagName();
		return $name;
	}
	function getNamespace() { return $this->namespace; }
	function getChildren() { return $this->children; }
	private function getChildrenByName($name) {
		$children = $this->getChildren();
		$matched_children = array();
		foreach($children as $child) {
			if (is_a($child, 'CWI_XML_Traversal') && $child->getTagName() == $name) array_push($matched_children, $child);
		}
		return $matched_children;
	}
	function getParent($path='') { return $this->parent; }
	
	/**
	 * Retrieves all data ("text") from this node and all child nodes
	 **/
	function getData($path=null) {
		$children = $this->getChildren();
		$data = '';
		
		if (is_null($path)) {
			foreach($children as $child) {
				if (is_object($child) && (
					is_a($child, 'XmlData') || is_a($child, 'CWI_XML_Data') ||
					is_a($child, 'XmlTraversal') || is_a($child, 'CWI_XML_Traversal')
				)) $data .= $child->getData(); // If this is a string then it is data, otherwise the type would be an object
			}
			return $data;
			#return html_entity_decode($this->_data);
		} else {
			if ($data = $this->getPathSingle($path)) {
				/*
				if (is_array($data)) {
					if (is_object($data[0])) return $data[0]->getData();
					else return false;
				} else {
					return $data->getData();
				}*/
				#return html_entity_decode($data->getData());
				return $data->getData();
			} else {
				return false;
			}
		}
	}
	/**
	 * Retrieves all data ("text") nodes that are direct children of this node
	 **/
	function getDataNodes() {
		$children = $this->getChildren();
		$data_children = array();
		foreach($children as $child) {
			if (is_object($child) && (is_a($child, 'XmlData') || is_a($child, 'CWI_XML_Data'))) {
				array_push($data_children, $child);
			}
		}
		return $data_children;
	}
	
	function getParam($name, $default=false) { 
		if (isset($this->params[$name])) return $this->params[$name];
		else return $default;
	}
	function getParams() { return $this->params; }
	
	function setTagName($tag_name) { $this->tagName = $tag_name; }
	function setNamespace($namespace) { $this->namespace = $namespace; }
	
	/**
	 * Adds an CWI_XML_Traversal obj to the children stack; additionally, automatically sets the child's parent to be $this
	 * @param CWI_XML_Traversal $xml_traversal_obj a child to be appended
	 * @return void
	 */
	function addChild(&$xml_traversal_obj, $attach_parent=true, $add_to_top=false) {
		// Make the parent of the child object $this object
		if (is_object($xml_traversal_obj) && (is_a($xml_traversal_obj, 'XmlTraversal') || is_a($xml_traversal_obj, 'CWI_XML_Traversal') )) {
			if ($attach_parent) {
				$xml_traversal_obj->setParent($this);
			}
		}
		// Add the child to the children stack
		if ($add_to_top) {
			array_unshift($this->children, $xml_traversal_obj);
		} else {
			array_push($this->children, $xml_traversal_obj);
		}
	}
	function addChildToTop(&$xml_traversal_obj, $attach_parent=true) {
		$this->addChild($xml_traversal_obj, $attach_parent, true);
	}
	
	function setChildren($children) {
		$this->children = $children;
	}
	private function removeChildAt($child_index) {
		array_splice($this->children, $child_index, 1, array());
	}
	function setParent(&$parent_xml_traversal_obj) {
		$this->parent = &$parent_xml_traversal_obj;
	}
	function removeParent() {
		$this->parent = null;
	}

	function setData($data) {
		// Need to implement
		for($i=0; $i < count($this->children); $i++) {
			if (is_a($this->children[$i], 'XmlData') || is_a($this->children[$i], 'CWI_XML_Data')) array_splice($this->children, $i, 1, array());
		}
		array_unshift($this->children, new CWI_XML_Data($data));
	}
	
	
	function setParam($name, $value) { $this->params[$name] = $value; }

	function setParams($params=array()) { $this->params = $params; }
	
	function removeParam($name) {
		if (isset($this->params[$name])) unset($this->params[$name]);
	}
	
	function remove() {
		if ($parent = $this->getParent()) {
			$parent_children = $parent->getChildren();
			$c_children = count($parent_children);
			for ($i=0; $i <= $c_children; $i++ ) {
				if (is_a($parent_children[$i], 'XmlTraversal') || is_a($parent_children[$i], 'CWI_XML_Traversal')) {
					if ($parent_children[$i] === $this) {
						$parent->removeChildAt($i);
						break;
					}
				}
			}
		}
	}
	
	function isRoot() {
		$parent = $this->getParent();
		return (empty($parent));
	}
	
	function getRoot() {
		if ($this->isRoot()) { // if (empty($parent)) {
			$return_xml_traversal = $this;
		} else {
			$parent = $this->getParent();
			$return_xml_traversal = $parent->getRoot();
		}
		// Make sure that the root is properly in-tact
		if (strlen($return_xml_traversal->getTagName()) > 0) {
			$root = new CWI_XML_Traversal();
			$root->addChild($return_xml_traversal);
			return $root;
		}
		
		return $return_xml_traversal;
	}
	
	function evaluate($path) {
		return CWI_XML_XPATH_Expr::createFromContextAndString($this, $path);
	}
	
	function getPath($path, $level = 0, $show_debug=false) {

		/**
		 * Path evaluated as:
		 * 	- node-set
		 * 	- boolean
		 * 	- number
		 * 	- string
		 */

#		$expression = CWI_XML_Expression::createFromContextAndString($this, $path);

		if ($path == '.' || empty($path)) { // Requesting current node
			return $this;
		} else if ($path == '..') { // Requesting parent node
			return $this->getParent(); // Added 01/27/2010 - still needs thorough testing
		} else if (substr($path, 0, 2) == '//') {
			return false; // Not supported yet
		}

		if (strpos($path, '->') > 0) { // Old style, not proper XPath Syntax using "->" instead of "/"; kept just so we do not break any "old" stuff
			/* SEARCHED AND REPLACED SOURCE - All instances of this should be gone by now */
			$path_elements = explode('->', $path);
		} else { // Valid XPath Syntax
			$path_elements = explode('/', $path);
		}
/*
<root>test<child></root>
getPath('/') => CWI_XML_Traversal()
getPath('/root') => CWI_XML_Traversal('root');
getPath('root') => CWI_XML_Traversal('root');

getPath('/root/child') => CWI_XML_Traversal('child');
getPath('/child') => false
getPath('child') => CWI_XML_Traversal('child')
*/
#if ($path == '/') array_pop($path_elements);
		$count_path_elements = count($path_elements);

		if (strlen($path_elements[0]) == 0) { // If blank then we are looking for the root
			$current_object = $this->getRoot();
			
			array_shift($path_elements);
			$count_path_elements --;

			#if ($path_elements[0] == $current_object->getTagName()) array_shift($path_elements);
			#else return false; // Not the correct tag
			#/* No longer applicable

			#if (empty($path_elements[0])) {

			if (empty($path_elements[0])) { // Root request only (XPATH=/)
				#echo 'ONE<br />';
				return array($current_object);
			} else {
				return $current_object->getPath( implode('/', $path_elements) );
			}
		/**
		 * User is asking for an XPath statement on a root element, so we must carefully look at whether they are requesting the root's tag name, as in:
		 * $root = new CWI_XML_Traversal('root')
		 * $child = new CWI_XML_Traversal('child'); // Not needed, just putting here to show a real world example
		 * $root->addChild($child)
		 *
		 * $xml_root_node = $root->getPathSingle('root'); // <-- Note how this should still select the root element (i.e.this)
		 */
		/*} else if ($this->isRoot()) {

			if ($path_elements[0] == $this->getFullTagName()) { // Check whether the requested tag
								
				array_shift($path_elements);
				$count_path_elements--;
				
				if ($count_path_elements == 0) { // The request was only for this tag
					return array($this);
				} else { // The request was for this tag and children tags
					$current_object = $this;
				}

			} else {
				return false;
			}
		*/	
		} else { // Not requesting root, use current object
			$current_object = $this;
		}

		$count_elements = count($path_elements);

		if ($count_elements > 0) {
			$current_element = $path_elements[0];
			
			/**
			 * Check whether additional attributes need to be matched [@attr=value]
			 */
			$requirements = array();
			$open_bracket = strpos($current_element, '[');
			
			// Look for search requirements in the format [@var="val"] - this assumes there is only one requirement and could be easily expanded in the future
			if ($open_bracket !== false) {
				$close_bracket = strpos($current_element, ']', $open_bracket);
				$tmp_requirements = substr($current_element, $open_bracket+1, $close_bracket-$open_bracket-1);
				$tmp_requirements = str_replace('"', '', str_replace('@', '', $tmp_requirements)); // Replace quotes and @sign
		
				list($name,$value) = explode('=', $tmp_requirements);
				if (substr($value, 0, 1) == "'" && substr($value, strlen($value)-1, 1) == "'") $value = substr($value, 1, strlen($value)-2);
		
				/**
				 * Add to list of requirements
				 */
				$requirements[$name] = $value;
				$current_element = substr($current_element, 0, $open_bracket);
			}

			/**
			 * Check whether namespace should be checked in the match
			 */
			$colon_pos = strpos($current_element, ':');
			if ($colon_pos > 0) {
				$check_namespace = true;
				$required_namespace = substr($current_element, 0, $colon_pos);
				$current_element = substr($current_element, $colon_pos+1);
			} else {
				$check_namespace = false;
			}

			$children_by_name = $current_object->getChildrenByName($current_element);

			$return_children = array();
			if (count($children_by_name) > 0) {
				$next_path_elements = array_slice($path_elements, 1);
				$is_final_element = ( ($count_elements-1) == 0);
				
				/**
				 * Check if any additional requirements were set above, such as attribute matching [@var="val"]
				 */
				
				if (count($requirements) > 0) {
					
					$reset_array = array();
					
					foreach($children_by_name as $element) {
						$tmp_use = true;
						#$element_params = $element->_params;
						$element_params = $element->getParams();
		
						foreach($requirements as $req_name=>$req_val) {
							if (!isset($element_params[$req_name])) {
								$tmp_use = false;
							} else {
								if ($element_params[$req_name] != $req_val) $tmp_use = false;
							}
						}
						
						if ($tmp_use) {
							$reset_array[] = $element;
						}
					}
		
					/**
					 * DONT THINK WE NEED THIS ANYMORE (11/27/2009): 
					 *	$current_object->setChildren($reset_array);
					 * BECAUSE OF THIS: 
					 **/
					$children_by_name = $reset_array;
				}

				/**
				 * Filter out any children that may not fit any specified namespaces
				 */
				if ($check_namespace) {
					$temp_obj = array();
					
					#foreach($return_obj as $element) {
					foreach($children_by_name as $element) {
						if ($element->getNamespace() == $required_namespace) {
							array_push($temp_obj, $element);
						}
					}
					$children_by_name = $temp_obj;
				}
				/**
				 * Finally, continue to traverse the remaining children to retrieve final matches
				 */	
				foreach($children_by_name as $child) {
					$valid_branch = true;
					
					if ($is_final_element) {
						array_push($return_children, $child);
					} else {
						if ($get_branch = $child->getPath( implode('/', $next_path_elements) )) {
							$return_children = array_merge($return_children, $get_branch);
						}
					}
				}
				
				return $return_children;
			} else {
				return false;
			}
		
		} else {
			return false;
		}

		/*
		#if (!in_array($path, array('config', 'settings', 'var', 'pages', 'pathMappings/add', 'requestHandlers/add', 'requireSecureConnection/add', 'database', 'tables', 'add', 'membership', 'providers/add', 'roleManager', 'profile', 'databaseConnections/add', 'stylesheet', 'template'))) {
			$count = 0;
			foreach($current_object as $obj) {
				$count ++;
				if (is_object($obj)) $obj->contextPosition = $count;
			}
		#}
		*/

	}
	
	function getPathSingle($path, $show_debug=false) {
		if ($object = $this->getPath($path, 0, $show_debug)) {
			if (is_array($object)) {
				return $object[0];
			} else if (is_a($object, 'XmlTraversal') || is_a($object, 'CWI_XML_Traversal')) {
				return $object;
			} else {
				return false;
			}
		} else return false;
	}
	
	function getPathSingleLast($path, $show_debug=false) {
		if ($object = $this->getPath($path, 0, $show_debug)) {
			if (is_array($object)) {
				return $object[count($object)-1];
			} else if (is_a($object, 'XmlTraversal') || is_a($object, 'CWI_XML_Traversal')) {
				return $object;
			} else {
				return false;
			}
		} else return false;
	}
	
	function toXml() { return $this->render(); } // Alias for render
	
	function render() {
		$output_xml = '';
		$children = $this->getChildren();

		$tag_name = $this->getTagName();
		$namespace = $this->getNamespace();
		
		if (count($children) > 0) {
			if (!empty($tag_name)) {
				$open_tag = new CWI_XML_Tag($tag_name, $this->getParams(), CWI_XML_Tag::TYPE_OPEN);
				$open_tag->setNamespace($namespace);
				$output_xml .= $open_tag->render();
			}
			
			foreach($children as $child) {
				$output_xml .= $child->render();
			}
			
			if (!empty($tag_name)) {
				$close_tag = new CWI_XML_Tag($tag_name, array(), CWI_XML_Tag::TYPE_CLOSE);
				$close_tag->setNamespace($namespace);
				$output_xml .= $close_tag->render();
			}
		} else {
			if (empty($tag_name)) { // Data only
				$output_xml .= $this->getData();
			} else {
				$open_close_tag = new CWI_XML_Tag($tag_name, $this->getParams(), CWI_XML_Tag::TYPE_OPENCLOSE);
				$open_close_tag->setNamespace($namespace);
				$output_xml .= $open_close_tag->render();
			}
		}
		
		return $output_xml;
	}
	/**
	 * Outputs the literal structure of the XML hierarchicy - not including data
	 * @param integer indent_size the number of spaces for each indentation
	 */
	function debug($indent_size=5, $level=0) {
		$output = '';
		$children = $this->getChildren();

		for ($i=0; $i < $level; $i++) {
			for ($x=0; $x < $indent_size; $x++) $output .= ' ';
			
		}
		$namespace = $this->getNamespace();
		if (!empty($namespace)) $output .= $namespace . ':';
		$output .= $this->getTagName();
		$params = $this->getParams();
		if (count($params) > 0) {
			$output .= ' [';
			$param_stack = array();
			foreach($params as $key=>$value) {
				array_push($param_stack, $key);
			}

			$output .= implode('; ', $param_stack);
			$output .= ']';
		}
		$output .= "\r\n";
		
		foreach($children as $child) {
			if (is_a($child, 'CWI_XML_Traversal')) {
				$output .= $child->debug($indent_size, $level+1);				
			} else if (is_a($child, 'CWI_XML_Data')) {
				for ($i=0; $i <= $level; $i++) for ($x=0; $x < $indent_size; $x++) $output .= ' ';
				$output .= '<span style="color:#999;">[CWI_XML_Data Node]</span>' . "\r\n";
			}
		}
		return $output;
	}
}
class XmlTraversal extends CWI_XML_Traversal {} // Alias for CWI_XML_Traversal to keep backwords compatibility
//class CWI_XML_TraversalRoot extends CWI_XML_Traversal {}

define('XMLTAG_OPEN', 'Open');
define('XMLTAG_CLOSE', 'Close');
define('XMLTAG_OPENCLOSE', 'OpenClose');

class CWI_XML_Data {
	private $data;
	function __construct($data) { $this->data = $data; }
	function getData() { return $this->data; }
	//function render() { return $this->getData(); }
	function render() { return htmlentities($this->getData()); }
}
class XmlData extends CWI_XML_Data {} // Alias for CWI_XML_Data to keep backwords compatibility

class CWI_XML_Tag {
	const TYPE_OPEN = 'Open';
	const TYPE_CLOSE = 'Close';
	const TYPE_OPENCLOSE = 'OpenClose';
	const TYPE_INSTRUCTION = 'Instruction';
	const TYPE_DECLARATION = 'Declaration';
	const TYPE_COMMENT = 'Comment';
	const TYPE_UNKNOWN = 'Unknown';
	
	private $params = array();
	private $namespace;
	private $name;
	private $type; // Open|Close|OpenClose
	function __construct($name='', $params=array(), $type=CWI_XML_Tag::TYPE_OPEN) {
		$this->setName($name);
		$this->setParams($params);
		$this->setType($type);
	}
	public function render() {
		$xml_tag = '<';
		// Close Tag
		if (strtolower($this->type) == 'close') $xml_tag .= '/';
		
		// Add Tag Namespace
		if (!empty($this->namespace)) $xml_tag .= $this->namespace . ':';
		
		// Add Tag Name
		if (!empty($this->name)) $xml_tag .= $this->name;
		
		if (strtolower($this->type) != 'close' && count($this->params)) {
			foreach($this->params as $aname=>$avalue) {
				#$xml_tag .= ' ' . $aname . '="' . str_replace('"', '&quot;', $avalue) . '"';
				$xml_tag .= ' ' . $aname . '="' . htmlentities($avalue) . '"';
			}
		}
		
		// Close Tag
		if ($this->type == 'OpenClose') $xml_tag .= ' /';
		$xml_tag .= '>';
		return $xml_tag;
	}
	public static function parseTag($tag_text) { // Returns a new instance of CWI_XML_Tag

		$inner_tag_start = 1;
		$inner_tag_length = strlen($tag_text) - 2;

		if (substr($tag_text, 1, 1) == '!') {
			if (substr($tag_text, 2, 2) == '--') {
				$tag_type = CWI_XML_Tag::TYPE_COMMENT;
			} else if (substr($tag_text, 2, 7) == '[CDATA[') {
				$tag_type = CWI_XML_Tag::TYPE_UNKNOWN;
			} else {
				$tag_type = CWI_XML_Tag::TYPE_DECLARATION;
			}
		} else if (substr($tag_text, 1, 1) == '/') {
			$inner_tag_start += 1;
			$inner_tag_length -= 1;
			$tag_type = CWI_XML_Tag::TYPE_CLOSE; //'Close';
		} else if (substr($tag_text, -2, 1) == '/') {
			$inner_tag_length -= 1;
			$tag_type = CWI_XML_Tag::TYPE_OPENCLOSE; //'OpenClose';
		} else if (substr($tag_text, 1, 1) == '?') {
			$tag_type = CWI_XML_Tag::TYPE_INSTRUCTION;
			#$tag_type = 'PHP';
		} else {
			$tag_type = CWI_XML_Tag::TYPE_OPEN; //'Open';
		}
		
		if ($tag_type == CWI_XML_Tag::TYPE_COMMENT) {
			$tag_obj = new CWI_XML_Tag();
			$tag_obj->setType($tag_type);
		} else {

			$tag_text = trim(substr($tag_text, $inner_tag_start, $inner_tag_length));

			$params = explode(' ', $tag_text, 2);

			if (strpos($params[0], ':') && $tag_type != CWI_XML_Tag::TYPE_INSTRUCTION) {//$tag_type != 'PHP') {
				list($tag_namespace, $tag_name) = explode(':', $params[0]);
			} else {
				$tag_namespace = '';
				$tag_name = $params[0];
			}
			if (isset($params[1])) {
				$params = $params[1];
			} else {
				$params = '';
			}
			
			// Double check that tag name and namespace are trimmed
			$tag_name = trim($tag_name);
			$tag_namespace = trim($tag_namespace);
			
			$tag_params = array();
			preg_match_all('/([a-zA-Z0-9_-]+)="(.*?)"/', $params, $search_params);
			if (isset($search_params[1]) && isset($search_params[2])) {
				for ($i=0; $i < count($search_params[1]); $i++) {
					$tag_params[$search_params[1][$i]] = html_entity_decode($search_params[2][$i]);
				}
			}
			
			// Create CWI_XML_Tag Object
			$tag_obj = new CWI_XML_Tag();
			$tag_obj->setParams($tag_params);
			$tag_obj->setName($tag_name);
			$tag_obj->setNamespace($tag_namespace);
			$tag_obj->setType($tag_type);
		}
		
		// Return Instatiated Object
		return $tag_obj;
	}
	public function getName() { return $this->name; }
	public function getNamespace() { return $this->namespace; }
	public function getParams() { return $this->params; }
	public function getFullName() {
		$name = '';
		if (strlen($this->getNamespace()) > 0) $name = $this->getNamespace() . ':';
		$name .= $this->getName();
	}
	public function getType() { return $this->type; }
	public function getParam($name) { if (isset($this->params[$name])) return $this->params[$name]; else return false; }
	
	public function setName($name) {
		$parts = explode(':', $name, 2);
		if (count($parts) == 2) {
			$this->setNamespace($parts[0]);
			$name = $parts[1];
		}
		$this->name = $name;
	}
	public function setNamespace($namespace) { $this->namespace = $namespace; }
	public function setParam($name, $value) { $this->params[$name] = $value; }
	public function setParams($params=array()) { $this->params = $params; }
	public function setType($type) { $this->type = $type; }
}
class XmlTag extends CWI_XML_Tag {} // Alias for CWI_XML_Tag to keep backwards compatibility

?>