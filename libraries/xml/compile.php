<?php

/**
 * CHANGELOG
 * 10/22/2009	(Robert Jones) Replaced the entire XmlCompile class to be more efficient (still need to add check for valid closing tag
 * 12/01/2009	(Robert Jones) Renamed XmlCompile to CWI_XML_Compile
 * 01/27/2009	(Robert Jones) Added throw/catch to CWI_XML_Compile to allow it to more gracefully handle various XML compile situations
 * 02/23/2010	(Robert Jones) Fixed bug where CWI_XML_Compile would fail when a comment contained XML code
 * 09/03/2010	(Robert Jones) Modified CWI_XML_Compile::compile so that it would decode data using html_entity_decode (with the exception of the instance that adds literal strings because <![CDATA[]]>)
 */

FrameworkManager::loadLibrary('xml.xml');

/**
 * EXCEPTIONS
 * 100  No valid elements were found in the file
 * 101	Multiple Roots
 * 102	Missing closing tag
 */
define('CWI_XML_ERROR_NO_ELEMENTS',	100);
define('CWI_XML_ERROR_MULTIPLE_ROOTS',	101);
define('CWI_XML_ERROR_TAG_MISMATCH',	102);

class CWI_XML_CompileOptionsStruct {
	public $stripSpaces		= 0;
	public $namespaces		= array();
	public $normalizeSpaces		= 0;
	public $namespaceTagsOnly	= false;
	public $ignoreMultipleRoots	= false;
	public $autoCloseTags		= false; // Whether to automatically mismatched closing tags (e.g. <outer><inner></outer> would automatically close <inner> when </outer> was found and </inner> had not been closed
	public $disableParents		= false; // By default (false) tags automatically attach parent objects when addChild is called.  Setting to true would prevent addChild from automatically adding the parent to child tags
}

class CWI_XML_CompileException extends Exception {}

class CWI_XML_Compile {
	private function processXml($xml, &$tag, $options, &$has_valid_tags, $start_index=0, $check_close_tag='', $iteration=0) {
		$len = strlen($xml);
	
		$data = '';
		for($i=$start_index; $i < $len; $i++) {
			$is_text = true;
			$char = $xml[$i];
			
			if ($char == '<') {
				
				$end = strpos($xml, '>', $i+1);
				
				if ($xml[$i+1] == '!') { // Check if this is a comment
					if (substr($xml, $i, 4) == '<!--') {
						$end_comment = strpos($xml, '-->', $i+1);
						if ($end_comment !== false) {
							$end = $end_comment + 3; // 3 = -->
						}
					}
				}
				
				if ($end === false) {
					return false;
				} else {

					$full_tag = substr($xml, $i, $end-$i+1);
					
					$xml_tag = CWI_XML_Tag::parseTag($full_tag);
										
					$is_text = false; // From this point forward, the text currently being processed will be assumed to be an XML tag
					
					/**
					 * At this point we are looking at an XML tag
					 * Now we need to check if only certain namespaces should be processed.
					 * For example, in XSL, we would want to process xsl namespaced tags, but ignore standard HTML tags
					 */
					if ($options->namespaceTagsOnly && count($options->namespaces) > 0) {
						$namespaces = array_values($options->namespaces);
						
						if ($xml_tag->getType() != CWI_XML_Tag::TYPE_UNKNOWN) { // More than likely this is a literal tag
							if (!in_array($xml_tag->getNamespace(), $namespaces)) {
								$is_text = true;
							}
						}
					}
					
					if (!$is_text) {
						
						if (strlen($data) > 0) {
							if ($has_valid_tags) {
								if (strlen(trim($data)) > 0) {
									$tag->addChild( new CWI_XML_Data(html_entity_decode($data)), (!$options->disableParents) );
								}
							}
							$data = '';
						}
						
						if ($xml_tag->getType() == CWI_XML_Tag::TYPE_OPEN || $xml_tag->getType() == CWI_XML_Tag::TYPE_OPENCLOSE) {
							$has_valid_tags = true;
							#for ($s=0; $s < ($iteration+1); $s++) echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							#echo $xml_tag->getName() . ' = ' . ($iteration+1) . ' = ' . number_format(memory_get_usage() / 1024) . 'kb<br />';
							$new_tag = new CWI_XML_Traversal();
							$new_tag->setTagName($xml_tag->getName());
							$new_tag->setNamespace($xml_tag->getNamespace());
							$new_tag->setParams($xml_tag->getParams());
							if ($xml_tag->getType() == CWI_XML_Tag::TYPE_OPENCLOSE) { //substr($xml, $end-1, 1) == '/') { // OpenClose
								
								$i = $end;
								$tag->addChild($new_tag, (!$options->disableParents));
								
							} else { // CWI_XML_Tag::TYPE_OPEN
								$new_index = $this->processXml($xml, $new_tag, $options, $has_valid_tags, $end+1, $xml_tag->getName(), $iteration+1);
								if (!is_int($new_index)) return false; // There was an error
								$i = $new_index;
								$tag->addChild($new_tag, (!$options->disableParents));
							}
						} else if ($xml_tag->getType() == CWI_XML_Tag::TYPE_CLOSE) {
							
							if ($check_close_tag != $xml_tag->getName()) {
								
								// Throw error for mismatched closing tag, unless autoCloseTags options is true
								if (!$options->autoCloseTags) throw new CWI_XML_CompileException('XML tag mismatched.  Closing tag found was \'' . $xml_tag->getName() . '\', but we were looking for \'' . $check_close_tag . '\'', CWI_XML_ERROR_TAG_MISMATCH);
								
							}

							#for ($s=0; $s < ($iteration); $s++) echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							#echo $xml_tag->getName() . ' = ' . ($iteration) . ' = ' . number_format(memory_get_usage() / 1024) . 'kb<br />';
							$i = $end;
							return $i;
						} else if ($xml_tag->getType() == CWI_XML_Tag::TYPE_DECLARATION || $xml_tag->getType() == CWI_XML_Tag::TYPE_INSTRUCTION) { // Ignore for now
							$i = $end;
						} else if ($xml_tag->getType() == CWI_XML_Tag::TYPE_COMMENT) { // Ignore for now
							$i = $end;
						} else { // CWI_XML_Tag::TYPE_UNKNOWN
							
							if (substr($full_tag, 2, 7) == '[CDATA[') {
								$check_cdata_open = '<![CDATA[';
								$check_cdata_open_len = 9;
								$check_cdata_close = ']]>';
	
								$close_pos = strpos($xml, $check_cdata_close, $i);
								$next_cdata = strpos($xml, $check_cdata_open, $i+1);
								
								while ( ($next_cdata < $close_pos) && ($next_cdata !== false) ) {
							
									$close_pos = strpos($xml, $check_cdata_close, $close_pos+strlen($check_cdata_close));
									$next_cdata = strpos($xml, $check_cdata_open, $next_cdata+strlen($check_cdata_open));
									
									if ($close_pos === false) break; // This should not happen, but if it did it could result in an endless loop
								}
								
								if ($close_pos !== false) {
									$literal_content = substr($xml, $i+$check_cdata_open_len, $close_pos-$i-$check_cdata_open_len);
									$tag->addChild( new CWI_XML_Data($literal_content), (!$options->disableParents) );
									
									$i = $close_pos + 2;
									continue;
								}
	
							}
							continue;
						}
					}
				}
			}
			
			if ($is_text) {
				$data .= $char;
				//$data
			}
		}
		if (strlen($data) > 0) {
			#if ($has_valid_tags) {
				if (strlen(trim($data)) > 0) {
					$tag->addChild( new CWI_XML_Data(html_entity_decode($data)), (!$options->disableParents));
				}
			#}
			$data = '';
		}
		return $i;
	}
	public static function compile($xml, $options=null) { // Static
		$xml_traversal = new CWI_XML_Traversal();
		$xml_process = new CWI_XML_Compile();
		if (!is_object($options) || (is_object($options) && !is_a($options, 'CWI_XML_CompileOptionsStruct'))) $options = new CWI_XML_CompileOptionsStruct();
		$has_valid_tags = false;

		try {
			$xml_process->processXml(trim($xml), $xml_traversal, $options, $has_valid_tags);
		} catch (Exception $e) {
			throw $e;
			return false;
		}
		$root_children = $xml_traversal->getChildren(); // Should only have 1 root, unless $options->ignoreMultipleRoots = true
			 
		if (count($root_children) > 1) {
			if ($options->ignoreMultipleRoots) {
				return $xml_traversal;
			} else {
				throw new CWI_XML_CompileException('Only one top level element is allowed in an XML document.', CWI_XML_ERROR_MULTIPLE_ROOTS);
			}
		} else if (count($root_children) == 1) {
			if ($has_valid_tags) {
				$root_children[0]->removeParent();
				return $root_children[0];
			} else {
				throw new CWI_XML_CompileException('XML parse error: No valid tags.', CWI_XML_ERROR_NO_ELEMENTS);
				return false;
			}
			
		} else return $xml_traversal;
	}
}
class XmlCompile extends CWI_XML_Compile {} // Alias for CWI_XML_Compile to keep backwords compatibility

?>