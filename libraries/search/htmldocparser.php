<?php

FrameworkManager::loadLibrary('search.abstractdocparser');
FrameworkManager::loadLibrary('search.textparser');

/**
 * Parser for HTML document
 **/
class CWI_SEARCH_HtmlDocParser extends CWI_SEARCH_AbstractDocParser {
	
	private $htmlBodyText = '';
	
	protected function parse() {
		
		$words = array();

		if (preg_match('#<title.*?>(.+)</title>#ims', $this->getRawDocument(), $match)) {
			
			$title = new CWI_SEARCH_TextParser(trim($match[1]));
			$words = array_merge($words, $title->getWords(false));
			
			$title = $match[1];
			$this->setTitle($title);
			
		}

		if (preg_match('#<body.*?>(.+)</body>#ims', $this->getRawDocument(), $match)) {
		
			$body_html = $match[1];
			// Remove comments
			$body_html = preg_replace('/<!--.+?-->/ims', '', $body_html);
			// Strip tabs
			#$body_html = str_replace("\t", ' ', $body_html);
			// Strip new line / return
			
			$body_html = str_replace("\t", '', $body_html);
			
			// Remove tags with content
			$remove = array('script','style');
			
			foreach($remove as $tag) {
				
				do {
					$start_tag_pos = strpos($body_html, '<' . $tag);
					$start_tag_end_pos = strpos($body_html, '>', $start_tag_pos);
					
					$end_tag = '</' . $tag . '>';
					$end_tag_pos = strpos($body_html, $end_tag, $start_tag_end_pos);
					$end_tag_len = strlen($end_tag);
					
					if ($start_tag_pos !== false && $start_tag_end_pos !== false && $end_tag_pos !== false) {
						
						$part1 = substr($body_html, 0, $start_tag_pos);
						$part2 = substr($body_html, $end_tag_pos+$end_tag_len);
						
						$body_html = $part1 . $part2;
						
					}
					
				} while ($start_tag_pos !== false);
				
			}
			
			// Strip tags
			$body_html = preg_replace('/<.+?>/', ' ', $body_html);
			$body_html = html_entity_decode($body_html);
			
			$body_html = preg_replace('#(\s\s+|[^a-z0-9\'\-]+)#ims', ' ', $body_html);

			$this->htmlBodyText = $body_html;
			
			$body = new CWI_SEARCH_TextParser($this->htmlBodyText);

			$words = array_merge($words, $body->getWords(false));
			
			//$word_frequency = array_count_values($words);
			
		} else {
			
			throw new Exception('Missing HTML body');
			
		}
		
		$this->setWords($words);
		
	}
	
	public function getHtmlBodyText() { return $this->htmlBodyText; }
	
}

?>