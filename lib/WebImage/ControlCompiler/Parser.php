<?php

namespace WebImage\ControlCompiler;

use FrameworkManager;
use CompileControl;
use CWI_XML_Tag;
use ControlConfigDictionary;

/**
 * Class Parser
 * Parses a string of text with controls (e.g. <cms:Control id="somename" />) and converts the text into a pre-compiled list of instructions for loading controls at run-time
 * @package WebImage\ControlCompiler
 */
class Parser {

	/**
	 * @param mixed $parent_id or array $parent_stack
	 * @param $buffer
	 */
	private static function createLiteral(Result $result, array $parent_stack, $buffer) {

		$id = uniqid( sprintf('tc_%s', CompileControl::getGenericControlId() ) );
		$parent_id = static::getParentId($parent_stack);

		$params = new ControlConfigDictionary(array(
			'id' => $id,
			'text' => $buffer
		));

		$result->createInitialization('Literal', $id, $params, $parent_id);

	}

	/**
	 * Gets the current parent id from the top of the stack, or returns null if we are at the root
	 * @param array $parent_stack
	 * @return null
	 */
	private static function getParentId(array $parent_stack) {
		$n = count($parent_stack);
		return ($n > 0) ? $parent_stack[$n-1]['id'] : null;
	}
	public static function parse($text) {

		$result = new Result();

		$parent_stack = array();

		$len_text = strlen($text);
		$buffer = '';

		FrameworkManager::loadLibrary('xml.xml');
		for($i=0; $i < $len_text; $i++) {

			if ($text[$i] == '<') {

				$outer_tag_start = $i;
				$outer_tag_length = strpos($text, '>', $i) - $outer_tag_start + 1;
				$outer_tag = substr($text, $outer_tag_start, $outer_tag_length);

				$xml_tag = CWI_XML_Tag::parseTag($outer_tag);

				$params = new ControlConfigDictionary($xml_tag->getParams());

				if (strlen($xml_tag->getNamespace()) == 0) {

					if (substr($outer_tag, 0, 6) == '<@Page') {

						$i += $outer_tag_length - 1;

						if ($master_page_file = $xml_tag->getParam('masterPageFile')) $result->addAutoLoadControlFile($master_page_file);

						if ($template_id = $xml_tag->getParam('templateId')) $result->addAutoLoadTemplate($template_id);

						if ($page_title = $xml_tag->getParam('title')) $result->setParam('pageTitle', $page_title);

						if ($meta_tag_description = $xml_tag->getParam('metaTagDescription')) $result->setParam('pageMetaDescription', $meta_tag_description);

						continue;

					}

				} else {

					$i += $outer_tag_length - 1;

					$id = $xml_tag->getParam('id');

					if (empty($id)) {
						$id = uniqid( sprintf('gc_%s', CompileControl::getGenericControlId() ) );
						$params->set('id', $id);
					}

					if (!empty($buffer)) static::createLiteral($result, $parent_stack, $buffer);
					$buffer = '';

					if ($xml_tag->getType() == CWI_XML_Tag::TYPE_OPEN || $xml_tag->getType() == CWI_XML_Tag::TYPE_OPENCLOSE) {

						$parent_id = static::getParentId($parent_stack);

						$result->createInitialization($xml_tag->getName(), $id, $params, $parent_id);

						if ($xml_tag->getType() == CWI_XML_Tag::TYPE_OPEN) {
							$parent_stack[] = array(
								'tag' => $xml_tag,
								'id' => $id
							);
						}

					} else if ($xml_tag->getType() == CWI_XML_Tag::TYPE_CLOSE) {

						array_pop($parent_stack);

					}

					continue;

				}

			}


			$buffer .= $text[ $i ];

		}

		if (!empty($buffer)) static::createLiteral($result, $parent_stack, $buffer);

		return $result;

	}

}