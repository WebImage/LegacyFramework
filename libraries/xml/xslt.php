<?php

class CWI_XML_XSLT_Template {
	private $match, $name;
	function __construct($match=null, $name=null) {
		$this->match = $match;
		$this->name = $name;
	}
	function createWithMatch($match) {
		$this->__construct($match);
	}
	function process() {
	}
}

function __constructTemplateStackAdd(&$stack, $element) {
	for ($i=count($stack)-1; $i >= 0; $i --) {
		$calc = $element['priority'] - $stack[$i]['priority'];
		if ($calc >= 0) {
			$stack = array_merge( array_slice($stack, 0, $i+1), array($element), array_slice($stack, $i+1) );
			return;
		}
	}
	array_unshift($stack, $element);
	return;
}
class CWI_XML_XslCompile {
	public function compile($xsl_xml, $options=null) { // Static
		if (!is_object($options) || (is_object($options) && !is_a($options, 'CWI_XML_CompileOptionsStruct'))) {
			$xsl_options = new CWI_XML_CompileOptionsStruct();
			$xsl_options->namespaceTagsOnly = true;
			$xsl_options->namespaces = array('http://www.w3.org/1999/XSL/Transform' => 'xsl');
		}

		$xsl_root = CWI_XML_Compile::compile( $xsl_xml, $xsl_options );
		return $xsl_root;
	}
}
class CWI_XML_XsltCompile extends CWI_XML_XslCompile {} // Alias
class CWI_XML_Xslt {
	
	/** 
	 * Top level <xsl:stylesheet /> elements include (sadly only <xsl:template> is implemented, lol):
	 * <xsl:import href="..."/>
	 * <xsl:include href="..."/>
	 * <xsl:strip-space elements="..."/>
	 * <xsl:preserve-space elements="..."/>
	 * <xsl:output method="..."/>
	 * <xsl:key name="..." match="..." use="..."/>
	 * <xsl:decimal-format name="..."/>
	 * <xsl:namespace-alias stylesheet-prefix="..." result-prefix="..."/>
	 * <xsl:attribute-set name="...">...</xsl:attribute-set>
	 * <xsl:variable name="...">...</xsl:variable>
	 * <xsl:param name="...">...</xsl:param>
	 * <xsl:template match="...">...</xsl:template>
	 * <xsl:template name="...">...</xsl:template>
	 */
	private $xml, $xsl;
	private $compiledXml, $compiledXsl;
	private $templates = array();
	
	function getXml() { return $this->xml; }
	function getXsl() { return $this->xsl; }
	function setXml($xml) {
		$this->xml = $xml;
		/**
		 * Compile XML text and return CWI_XML_Traversal object
		 */
		$xml_root = CWI_XML_Compile::compile( $xml );

		$this->setCompiledXml($xml_root);
	}
	function setXsl($xsl) {
		$this->xsl = $xsl;
		/**
		 * Compile XSL text and return XslTraversal object
		 */
		$xsl_root = CWI_XML_XslCompile::compile( $xsl );
		
		$this->setCompiledXsl($xsl_root);
	}
	
	public function getCompiledXml() { return $this->compiledXml; }
	public function getCompiledXsl() { return $this->compiledXsl; }
	/**
	 * @param CWI_XML_Traversal $xml_traversal compiled XML object (compiled meaning CWI_XML_Traversal object)
	 * @access public
	 */
	public function setCompiledXml($xml_traversal) { $this->compiledXml = $xml_traversal; }
	/**
	 * @param CWI_XML_Traversal $xsl_traversal compiled XML object (compiled meaning CWI_XML_Traversal object)
	 * @access public
	 */
	public function setCompiledXsl($xsl_xml_traversal) { $this->compiledXsl = $xsl_xml_traversal; }
	
	function render() {
		$xsl_root = $this->getCompiledXsl();
		$xml_root = $this->getCompiledXml();

		if ($stylesheet = $xsl_root->getPathSingle('/stylesheet')) {
			$this->templates = $stylesheet->getPath('template');
			
			if ($template = $this->getAssociatedTemplate($xml_root)) {
				// Wrap XML root in parent structure to make XPATH queries work better for xslt select methods in this class
				#$new_root = new CWI_XML_Traversal();
				#$new_root->addChild($xml_root);
				return $this->processTemplateChildren($template->getChildren(), $xml_root);
			} else {
				return $this->processXmlNodes($xml_root->getChildren());
			}
			return $this->processChildren($stylesheet->getChildren(), $xml_root);
		} else return false;
	}
	
	/**
	 * @param array Array containing a series of CWI_XML_Traversal objects
	 * @access private
	 * @return string The outputted rendered code
	 */
	private function processXmlNodes($xml_nodes, $mode=null) {
		$output = '';
		foreach($xml_nodes as $xml_node) {
			if (is_a($xml_node, 'XmlData') || is_a($xml_node, 'CWI_XML_Data')) {
				$output .= $xml_node->getData();
			} else {
				
				if ($template = $this->getAssociatedTemplate($xml_node, $mode)) {
					// Is template
					$output .= $this->processTemplateChildren($template->getChildren(), $xml_node, $mode);
				} else {
					// No template
					$output .= $this->processXmlNodes($xml_node->getChildren(), $mode);
				}
			}	
		}
		return $output;
	}
	
	/**
	 * @param array Array containing a series of CWI_XML_Traversal object, each of which is a child element of an <xsl:template />
	 * @access private Essentially an internal static function, as it has no impact on class properties
	 * @return string The outputted rendered code
	 */
	private function processTemplateChildren($xsl_arr, $xml_entry, $mode=null) {

		$output = '';

		foreach($xsl_arr as $xsl) {
			if (is_a($xsl, 'XmlData') || is_a($xsl, 'CWI_XML_Data')) {
				$output .= $xsl->getData();
			} else {
				if ($xsl->getNamespace() == 'xsl') {
					switch ($xsl->getTagName()) {
						case 'apply-templates':
							if ($select = $xsl->getParam('select')) {
								if ($xml_nodes = $xml_entry->getPath($select)) {
									$mode = null;
									if ($param_mode = $xsl->getParam('mode')) $mode = $param_mode;
									$output .= $this->processXmlNodes( $xml_nodes, $mode );
								}
							} else {
								$xml_node = $xml_entry;
								die("NO SELECT NOT IMPLEMENTED");
							}
							
							#$output .= $this->processXmlNodes($xml_nodes);
							break;
						case 'call-template':
							echo 'call-template<br />';
							if ($name = $xsl->getParam('name')) {
								echo 'Name: ' . $name . '<br />';
								if ($has_template = $this->getTemplateByName($name)) {
									$output = $this->processTemplateChildren($has_template->getChildren(), $xml_entry);
									echo '<pre>';
									echo htmlentities($output);exit;
									echo 'has Template<br />';
								}
							}
							break;
						case 'for-each':
							if ($select = $xsl->getParam('select')) {
								if ($xml_values = $xml_entry->getPath($select)) {
									foreach($xml_values as $xml_value) {
										$output .= $this->processTemplateChildren($xsl->getChildren(), $xml_value);
									}
								}
							}
							break;
						case 'value-of':
							if ($select = $xsl->getParam('select')) {
								/**h
								 * OrExpr		or
								 * AndExpr		and
								 * EqualityExpr		=, !=
								 * RelationExpr		<, >, <=, >=
								 
								 * VariableReference	$var-name
								 * Literal
								 * Number
								 * FunctionCall
								 */
								#$xml_entry->evaluate($select);
								#if ($xml_value = $xml_entry->getPathSingle($select)) {
								
								if ($xml_values = $xml_entry->getPath($select)) {
									if (is_object($xml_values)) {
										#return $xml_values->getData();
										$output .= $xml_values->getData();
									} else if (is_array($xml_values)) {
										foreach($xml_values as $xml_value) {
											$output .= $xml_value->getData();
										}
									} else {
										#return $xml_values;
										$output .= $xml_values;
									}
								}
							}
							break;
					}
				} else {
					$output .= $xsl->getData();
				}
			}
		}
		return $output;
	}
	
	private function getTemplateByName($name) {
		$templates = $this->templates;
		foreach($templates as $template) {
			if ($template_name = $template->getParam('name')) {
				if ($template_name == $name) return $template;
			}
		}
		return false;
	}
	private function getAssociatedTemplate($xml_entry, $mode=null) {
		$templates = $this->templates;
		$candidate_templates = array();
		$is_xml_root = $xml_entry->isRoot();

		foreach($templates as $template) {
			
			if ($match = $template->getParam('match')) {
				
				if (is_null($mode) || ($template->getParam('mode') == $mode)) {
				
					if ($xml_entry->isRoot() && $match = '/') { // Matched root element on a root node
						return $template;
					}
					
				
					$branches = explode('/', $match);
		
					$branches = array_reverse($branches);
					
					// If this is a root template, chop off the second branch as it will be empty anyway
					if ($match == '/') array_pop($branches);

					$check_node = $xml_entry;
					
					$possible_candidate = true;
					
					/**
					 * Priorities
					 * Based on http://www.codetoad.com/xml/xslt8.asp
					 * Three levels of priorities:
					 * 	1. Patterns that match a class of nodes, e.g. *, are assigned -0.5
					 *	2. Patterns that match by name only, such as "character," are assigned 0
					 *	3. Context matching nodes, such as "castmember/character" are assigned 0.5 - specificity does not matter, e.g. everyone/castmember/character does not receive a higher priority
					 */
					 
					// Simple pattern matching by tag name/path- need to implement more advanced filtering options

					$count_branches = count($branches);
					for ($i=0; $i < $count_branches; $i++) {
						$branch = $branches[$i];
						
						// If this branch is empty, then we are being asked if this is a ROOT element
						if (empty($branch) && !$check_node->isRoot()) {
							$possible_candidate = false;
						} else if (!empty($branch) && $branch != $check_node->getTagName()) {
							$possible_candidate = false;
						}
						
						if (!$possible_candidate) break; // No need to stick around
						
						$check_node = $check_node->getParent();

						if (empty($check_node)) {
							#$next_branch_is_root = ( ($i==($count_branches-2)) && empty($branches[$i+1]) );
							break; // No need to stick around
						}
					}
					
					if ($possible_candidate) {
						if (count($branches) > 1) $priority = 0.5;
						else $priority = 0;
	
						CWI_XML_XsltTemplateStackAdd($candidate_templates, array(
							'priority' => $priority,
							'template' => $template
							));
					}
				}
			}
		}
		$count = count($candidate_templates);

		if ($count >= 1) {
			return $candidate_templates[count($candidate_templates)-1]['template']; // Return last option since it has the highest priority
		} else {
			return false;
		}
		return false;
	}
	
}

?>
