<?php

FrameworkManager::loadLibrary('search.abstractdocparser');

/**
 * Basic text parser
 **/
class CWI_SEARCH_TextParser extends CWI_SEARCH_AbstractDocParser {
	
	protected function parse() {
		
		$raw = strtolower($this->getRawDocument());
		
		$raw = str_replace(array("\n", "\r", '.', ' - '), ' ', $raw);
		$words = str_word_count($raw, 1, '0123456789');
		$this->setWords($words);
		
	}
}

?>